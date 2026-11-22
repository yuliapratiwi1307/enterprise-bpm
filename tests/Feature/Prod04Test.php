<?php

namespace Tests\Feature;

use App\Models\MarketingBrief;
use App\Models\Prod04BriefVersion;
use App\Models\Prod04DistributionRecipient;
use App\Services\Prod04RecipientAccess;
use App\Models\Prod04ValidationTask;
use App\Models\User;
use App\Notifications\ProductionWorkflowNotification;
use App\Services\Prod04Workflow;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class Prod04Test extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('PROD-04 tests require APP_ENV=testing.');
        }

        $identity = DB::selectOne('select current_database() as database, current_user as username');
        if (
            $identity?->database !== 'enterprise_bpm_test'
            || $identity?->username !== 'enterprise_bpm_test_user'
        ) {
            throw new RuntimeException('PROD-04 tests are restricted to enterprise_bpm_test_user on enterprise_bpm_test.');
        }
    }

    public function test_only_marketing_owner_can_submit_a_ready_brief(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $otherMarketing = $this->createUserWithRole('other-marketing@prod04.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod04.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod04.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($otherMarketing)
            ->post(route('prod04.submit', $brief))
            ->assertForbidden();
        $this->assertDatabaseCount('prod04_brief_versions', 0);

        $this->actingAs($marketing)
            ->post(route('prod04.submit', $brief))
            ->assertRedirect();

        $version = Prod04BriefVersion::query()->sole();
        $this->assertSame('PENDING_ACKNOWLEDGEMENT', $version->distribution_status);
        $this->assertSame('SUBMITTED', $brief->fresh()->status);
        $this->assertDatabaseCount('prod04_validation_tasks', 1);
        $this->assertDatabaseCount('prod04_document_access', 2);
        Notification::assertSentTo(
            User::query()->whereHas('roles', fn ($query) => $query->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail(),
            ProductionWorkflowNotification::class,
            fn (ProductionWorkflowNotification $notification): bool => $notification->toDatabase($marketing)['event'] === 'BRIEF_DELIVERED',
        );
        Notification::assertSentTo(
            User::query()->whereHas('roles', fn ($query) => $query->where('code', 'ADMIN_PRODUKSI'))->firstOrFail(),
            ProductionWorkflowNotification::class,
            fn (ProductionWorkflowNotification $notification): bool => $notification->toDatabase($marketing)['event'] === 'BRIEF_COPY_DELIVERED',
        );
    }

    public function test_unready_brief_is_rejected_without_creating_an_official_snapshot(): void
    {
        $this->configureCalendar();
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod04.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod04.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);
        $brief->components()->where('component', 'quantity')->delete();

        $this->actingAs($marketing)
            ->post(route('prod04.submit', $brief))
            ->assertSessionHasErrors('brief');

        $this->assertDatabaseCount('prod04_brief_versions', 0);
        $this->assertSame('READY_FOR_SUBMISSION', $brief->fresh()->status);
    }

    public function test_submit_retry_is_idempotent_and_preserves_an_immutable_snapshot(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $assistantManager = $this->createUserWithRole('assistant-manager@prod04.test', 'ASST_MANAGER_PRODUKSI');
        $admin = $this->createUserWithRole('production-admin@prod04.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);
        $this->travelTo('2026-10-21 10:00:00 Asia/Jakarta');

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();
        $snapshot = $version->snapshot;
        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();

        $this->assertSame(1, Prod04BriefVersion::query()->count());
        $this->assertSame(1, Prod04ValidationTask::query()->count());
        $this->assertSame(2, $version->recipients()->count());
        $this->assertSame($snapshot, $version->fresh()->snapshot);
        $this->assertSame('BM-2026-0001', $version->document_number);
        $this->assertDatabaseHas('prod04_document_access', ['brief_version_id' => $version->id, 'user_id' => $assistantManager->id]);
        $this->assertDatabaseHas('prod04_document_access', ['brief_version_id' => $version->id, 'user_id' => $admin->id]);

        try {
            DB::transaction(fn () => DB::table('prod04_brief_versions')->where('id', $version->id)->update([
                'snapshot' => json_encode(['tampered' => true]),
            ]));
            $this->fail('The official snapshot must reject updates.');
        } catch (QueryException) {
            $this->assertSame($snapshot, $version->fresh()->snapshot);
        }
    }

    public function test_partial_copy_failure_can_be_retried_without_duplicate_primary_task_or_access(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $assistantManager = $this->createUserWithRole('assistant-manager@prod04.test', 'ASST_MANAGER_PRODUKSI');
        $admin = $this->createUserWithRole('production-admin@prod04.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);
        $failCopyRecipient = true;

        $access = Mockery::mock(Prod04RecipientAccess::class);
        $access->shouldReceive('grant')
            ->andReturnUsing(function (Prod04BriefVersion $version, Prod04DistributionRecipient $recipient, $availableAt) use (&$failCopyRecipient): void {
                if ($recipient->obligation_type === 'COPY' && $failCopyRecipient) {
                    throw new QueryException(
                        'pgsql',
                        'insert into prod04_document_access',
                        [],
                        new PDOException('simulated recipient failure')
                    );
                }

                (new Prod04RecipientAccess)->grant($version, $recipient, $availableAt);
            });

        $this->app->forgetInstance(Prod04Workflow::class);
        $this->app->instance(Prod04RecipientAccess::class, $access);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();
        $this->assertSame('DELIVERY_FAILED', $version->distribution_status);
        $this->assertSame(1, Prod04ValidationTask::query()->count());
        $this->assertDatabaseHas('prod04_document_access', ['brief_version_id' => $version->id, 'user_id' => $assistantManager->id]);
        $this->assertDatabaseMissing('prod04_document_access', ['brief_version_id' => $version->id, 'user_id' => $admin->id]);

        $failCopyRecipient = false;
        $this->actingAs($marketing)->post(route('prod04.retry', $version))->assertRedirect();

        $this->assertSame('PENDING_ACKNOWLEDGEMENT', $version->fresh()->distribution_status);
        $this->assertSame(1, Prod04ValidationTask::query()->count());
        $this->assertSame(2, $version->recipients()->where('delivery_status', 'DELIVERED')->count());
        $this->assertSame(1, $version->recipients()->where('user_id', $assistantManager->id)->value('attempt_count'));
        $this->assertDatabaseHas('prod04_document_access', ['brief_version_id' => $version->id, 'user_id' => $admin->id]);
        $this->assertDatabaseHas('prod04_events', ['brief_version_id' => $version->id, 'event_type' => 'DELIVERY_RETRIED']);
    }

    public function test_only_assistant_manager_can_acknowledge_and_validation_start_ack_is_automatic(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $assistantManager = $this->createUserWithRole('assistant-manager@prod04.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod04.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $this->actingAs($marketing)
            ->post(route('prod04.acknowledge', $version))
            ->assertForbidden();
        $this->actingAs($assistantManager)
            ->post(route('prod04.acknowledge', $version))
            ->assertRedirect();

        $acknowledgement = $version->acknowledgement()->sole();
        $this->assertSame('MANUAL', $acknowledgement->method);
        $this->assertSame('ACKNOWLEDGED', $version->fresh()->distribution_status);

        $secondBrief = $this->createReadyBrief($marketing, 'REQ-2026-0002');
        $this->actingAs($marketing)->post(route('prod04.submit', $secondBrief))->assertRedirect();
        $secondVersion = Prod04BriefVersion::query()->where('marketing_brief_id', $secondBrief->id)->sole();
        $task = $secondVersion->validationTask()->sole();
        $automaticAck = app(Prod04Workflow::class)->acknowledgeForValidationStart($task, $assistantManager);

        $this->assertSame('AUTOMATIC', $automaticAck->method);
        $this->assertSame('ACKNOWLEDGED', $secondVersion->fresh()->distribution_status);
    }

    public function test_production_admin_receives_read_access_but_cannot_acknowledge(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod04.test', 'ASST_MANAGER_PRODUKSI');
        $productionAdmin = $this->createUserWithRole('production-admin@prod04.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $this->actingAs($productionAdmin)
            ->get(route('prod04.index'))
            ->assertOk()
            ->assertSee($version->document_number);
        $this->get(route('prod04.show', $version))->assertOk();
        $this->post(route('prod04.acknowledge', $version))->assertForbidden();
        $this->assertDatabaseCount('prod04_acknowledgements', 0);
        $this->assertSame('PENDING_ACKNOWLEDGEMENT', $version->fresh()->distribution_status);
    }

    public function test_bpm_administrator_can_retry_failed_delivery_but_cannot_edit_or_acknowledge_brief(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod04.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod04.test', 'ADMIN_PRODUKSI');
        $bpmAdministrator = $this->createUserWithRole('bpm-admin@prod04.test', 'ADMIN_BPM');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();
        $copyRecipient = $version->recipients()->where('obligation_type', 'COPY')->firstOrFail();
        $copyRecipient->update([
            'delivery_status' => 'DELIVERY_FAILED',
            'last_error' => 'Simulated failure.',
        ]);
        $version->update(['distribution_status' => 'DELIVERY_FAILED']);

        $this->actingAs($bpmAdministrator)
            ->get(route('prod04.index'))
            ->assertOk()
            ->assertSee($version->document_number);
        $this->get(route('prod04.show', $version))->assertOk();
        $this->post(route('prod04.acknowledge', $version))->assertForbidden();
        $this->put(route('prod03.briefs.update', $brief), [
            'components' => ['quantity' => ['value' => 'Modified by administrator']],
        ])->assertForbidden();
        $this->post(route('prod04.retry', $version))->assertRedirect();

        $this->assertSame('PENDING_ACKNOWLEDGEMENT', $version->fresh()->distribution_status);
        $this->assertDatabaseHas('marketing_brief_components', [
            'marketing_brief_id' => $brief->id,
            'component' => 'quantity',
            'value' => 'One unit',
        ]);
        $this->assertDatabaseHas('prod04_events', [
            'brief_version_id' => $version->id,
            'event_type' => 'DELIVERY_RETRIED',
        ]);
    }

    public function test_working_calendar_counts_working_hours_and_sends_one_reminder_then_overdue(): void
    {
        $this->configureCalendar([
            'timezone' => 'Asia/Jakarta',
            'weekly_intervals' => ['wednesday' => [['08:30', '12:00'], ['13:00', '17:30']]],
            'national_holidays' => [],
            'holiday_source' => 'Test fixture',
            'holiday_data_checksum' => hash('sha256', json_encode([])),
            'holiday_verified_at' => '2026-01-01',
            'holiday_covered_years' => [2026],
        ]);
        Notification::fake();
        $this->travelTo('2026-10-21 08:30:00 Asia/Jakarta');
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $assistantManager = $this->createUserWithRole('assistant-manager@prod04.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod04.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();
        $workflow = app(Prod04Workflow::class);

        $this->travel(3)->hours();
        $workflow->processSla();
        $this->assertNotNull($version->slaTracking()->firstOrFail()->reminder_sent_at);
        $this->assertNull($version->slaTracking()->firstOrFail()->overdue_detected_at);
        Notification::assertSentTo($assistantManager, ProductionWorkflowNotification::class, fn (ProductionWorkflowNotification $notification): bool => $notification->toDatabase($assistantManager)['event'] === 'ACK_REMINDER');

        $this->travel(2)->hours();
        $workflow->processSla();

        $tracking = $version->slaTracking()->firstOrFail();
        $this->assertNotNull($tracking->overdue_detected_at);
        $this->assertSame('PENDING_ACKNOWLEDGEMENT', $version->fresh()->distribution_status);
        $this->assertSame(1, $version->events()->where('event_type', 'REMINDER_SENT')->count());
        $this->assertSame(1, $version->events()->where('event_type', 'ACK_OVERDUE_DETECTED')->count());
    }

    public function test_submission_requires_calendar_and_both_recipient_roles(): void
    {
        $marketing = $this->createUserWithRole('marketing@prod04.test', 'MARKETING');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)
            ->post(route('prod04.submit', $brief))
            ->assertSessionHasErrors('distribution');

        $this->assertDatabaseCount('prod04_brief_versions', 0);
    }

    private function createUserWithRole(string $email, string $roleCode): User
    {
$user = User::create([
    'name' => str_replace('_', ' ', $roleCode) . ' Test',
    'email' => $email,
    'password' => bcrypt('Prod04TestOnlyPassword!'),
]);        $roleId = DB::table('bpm_roles')->where('code', $roleCode)->value('id');
        if ($roleId === null) {
            $roleId = DB::table('bpm_roles')->insertGetId([
                'code' => $roleCode,
                'name' => str_replace('_', ' ', $roleCode),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $user->roles()->attach($roleId);

        return $user;
    }

    private function createReadyBrief(User $marketing, string $requestNumber = 'REQ-2026-0001'): MarketingBrief
    {
        $clientBriefId = DB::table('client_briefs')->insertGetId([
            'request_number' => $requestNumber,
            'status' => 'RECORDED',
            'client_name' => 'PROD-04 Test Client',
            'communication_source' => 'Email',
            'requirement_type' => 'Display',
            'description' => 'Test marketing brief',
            'marketing_user_id' => $marketing->id,
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $brief = MarketingBrief::query()->create([
            'client_brief_id' => $clientBriefId,
            'marketing_user_id' => $marketing->id,
            'status' => 'READY_FOR_SUBMISSION',
            'readiness_snapshot' => [
                'ready' => true,
                'status' => 'READY_FOR_SUBMISSION',
                'missing_components' => [],
                'critical_outstanding' => [],
                'notes' => [],
            ],
        ]);

        foreach ([
            'work_type' => 'Booth',
            'work_concept' => 'Modern booth',
            'visualization' => '3D render reference',
            'work_specification' => 'Modular construction',
            'duration' => 'Five days',
            'quantity' => 'One unit',
        ] as $key => $value) {
            $brief->components()->create([
                'component' => $key,
                'value' => $value,
                'is_provisional' => false,
            ]);
        }

        return $brief;
    }

    /**
     * @param  array<string, mixed>|null  $calendar
     */
    private function configureCalendar(?array $calendar = null): void
    {
        config([
            'prod04.working_calendar' => $calendar ?? [
                'timezone' => 'Asia/Jakarta',
                'weekly_intervals' => [
                    'monday' => [['08:30', '12:00'], ['13:00', '17:30']],
                    'tuesday' => [['08:30', '12:00'], ['13:00', '17:30']],
                    'wednesday' => [['08:30', '12:00'], ['13:00', '17:30']],
                    'thursday' => [['08:30', '12:00'], ['13:00', '17:30']],
                    'friday' => [['08:30', '12:00'], ['13:00', '17:30']],
                ],
                'national_holidays' => [],
                'holiday_source' => 'Test fixture',
                'holiday_data_checksum' => hash('sha256', json_encode([])),
                'holiday_verified_at' => '2026-01-01',
                'holiday_covered_years' => [2026],
            ],
        ]);
    }
}
