<?php

namespace Tests\Feature;

use App\Models\ClientBrief;
use App\Models\DesignRequest;
use App\Models\MarketingBrief;
use App\Models\User;
use App\Notifications\ProductionWorkflowNotification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PgSql\Connection;
use Tests\TestCase;

class Prod03Test extends TestCase
{
    use RefreshDatabase;

    public function test_only_marketing_can_start_marketing_brief_from_recorded_client_brief(): void
    {
        $marketing = $this->createUser('marketing@prod03.test');
        $this->grantRole($marketing, 'MARKETING');
        $source = $this->createRecordedClientBrief($marketing);

        $this->actingAs($marketing)
            ->post(route('prod03.briefs.store', $source))
            ->assertRedirect();

        $brief = MarketingBrief::query()->sole();
        $this->assertSame('DRAFT', $brief->status);
        $this->assertSame($source->id, $brief->client_brief_id);
        $this->assertNotNull($source->fresh()->stage_3_started_at);
        $this->assertDatabaseHas('client_brief_events', [
            'client_brief_id' => $source->id,
            'event_type' => 'STAGE_3_STARTED',
            'actor_role' => 'MARKETING',
        ]);
        $this->assertDatabaseHas('production_workflow_events', [
            'marketing_brief_id' => $brief->id,
            'event_type' => 'BRIEF_DRAFT_CREATED',
            'actor_user_id' => $marketing->id,
        ]);
        try {
            DB::transaction(fn () => DB::table('production_workflow_events')
                ->where('marketing_brief_id', $brief->id)
                ->update(['reason' => 'mutated']));
            $this->fail('Audit events must reject updates.');
        } catch (QueryException) {
            $this->assertDatabaseHas('production_workflow_events', [
                'marketing_brief_id' => $brief->id,
                'event_type' => 'BRIEF_DRAFT_CREATED',
                'reason' => null,
            ]);
        }

        $designer = $this->createUser('designer@prod03.test');
        $this->grantRole($designer, 'DESIGNER');
        $this->actingAs($designer)
            ->get(route('prod03.briefs.index'))
            ->assertForbidden();
        $this->get(route('prod03.task-pool.index'))->assertOk();
        $this->post(route('prod03.briefs.store', $source))
            ->assertForbidden();

        $this->actingAs($marketing)
            ->get(route('prod03.task-pool.index'))
            ->assertForbidden();

        $this->actingAs($designer)
            ->post(route('prod03.briefs.store', $source))
            ->assertForbidden();

        $draftSource = $this->createClientBrief($marketing, 'DRAFT');
        $this->actingAs($marketing)
            ->post(route('prod03.briefs.store', $draftSource))
            ->assertNotFound();
    }

    public function test_marketing_brief_can_save_incomplete_draft_and_readiness_requires_all_components(): void
    {
        $marketing = $this->createMarketing();
        $source = $this->createRecordedClientBrief($marketing);

        $this->actingAs($marketing)
            ->post(route('prod03.briefs.store', $source))
            ->assertRedirect();

        $brief = MarketingBrief::query()->sole();
        $this->put(route('prod03.briefs.update', $brief), [
            'components' => [
                'work_type' => ['value' => 'Booth'],
                'work_concept' => ['value' => ''],
            ],
        ])->assertRedirect(route('prod03.briefs.edit', $brief));

        $this->post(route('prod03.briefs.validate-readiness', $brief))
            ->assertRedirect(route('prod03.briefs.show', $brief));

        $brief->refresh();
        $this->assertSame('DRAFT', $brief->status);
        $this->assertContains('Visualisasi', $brief->readiness_snapshot['missing_components']);

        $this->put(route('prod03.briefs.update', $brief), ['components' => $this->completeComponents()])
            ->assertRedirect(route('prod03.briefs.edit', $brief));
        $this->post(route('prod03.briefs.validate-readiness', $brief))->assertRedirect();

        $this->assertSame('READY_FOR_SUBMISSION', $brief->fresh()->status);
        $this->assertDatabaseHas('production_workflow_events', [
            'marketing_brief_id' => $brief->id,
            'event_type' => 'BRIEF_READY',
        ]);
    }

    public function test_marketing_users_cannot_read_or_edit_another_users_marketing_brief(): void
    {
        $owner = $this->createMarketing();
        $otherMarketing = $this->createMarketing();
        $brief = $this->createMarketingBrief($owner, $this->createRecordedClientBrief($owner));

        $this->actingAs($otherMarketing)
            ->get(route('prod03.briefs.show', $brief))
            ->assertForbidden();
        $this->get(route('prod03.briefs.edit', $brief))->assertForbidden();
        $this->put(route('prod03.briefs.update', $brief), ['components' => $this->completeComponents()])
            ->assertForbidden();
        $this->get(route('prod03.briefs.index'))
            ->assertOk()
            ->assertDontSee(route('prod03.briefs.show', $brief));
    }

    public function test_provisional_component_requires_a_value_and_information_basis_to_be_ready(): void
    {
        $marketing = $this->createMarketing();
        $source = $this->createRecordedClientBrief($marketing);
        $brief = $this->createMarketingBrief($marketing, $source);
        $components = $this->completeComponents();
        $components['duration']['is_provisional'] = '1';

        $this->actingAs($marketing)->put(route('prod03.briefs.update', $brief), ['components' => $components]);
        $this->post(route('prod03.briefs.validate-readiness', $brief));
        $this->assertSame('DRAFT', $brief->fresh()->status);

        $components['duration']['information_basis'] = 'Estimasi berdasarkan kebutuhan awal klien';
        $this->put(route('prod03.briefs.update', $brief), ['components' => $components]);
        $this->post(route('prod03.briefs.validate-readiness', $brief));
        $this->assertSame('READY_FOR_SUBMISSION', $brief->fresh()->status);
    }

    public function test_design_task_take_revision_release_versioning_and_acceptance_are_audited(): void
    {
        Storage::fake('local');
        $marketing = $this->createMarketing();
        $source = $this->createRecordedClientBrief($marketing);
        $brief = $this->createReadyMarketingBrief($marketing, $source);
        $designer = $this->createUser('designer-one@prod03.test');
        $replacement = $this->createUser('designer-two@prod03.test');
        $this->grantRole($designer, 'DESIGNER');
        $this->grantRole($replacement, 'DESIGNER');
        $designRequest = $this->createDesignRequest($marketing, $brief);

        $this->actingAs($designer)
            ->post(route('prod03.task-pool.take', $designRequest))
            ->assertRedirect(route('prod03.design-requests.show', $designRequest));
        $this->assertSame($designer->id, $designRequest->fresh()->current_pic_id);

        $this->post(route('prod03.task-pool.submit-result', $designRequest), [
            'attachment' => UploadedFile::fake()->create('concept-v1.pdf', 1, 'application/pdf'),
        ])->assertRedirect();
        $notification = $marketing->notifications()
            ->where('type', ProductionWorkflowNotification::class)
            ->latest('created_at')
            ->first();
        $this->assertNotNull($notification);
        $this->assertSame('DESIGN_RESULT_SUBMITTED', $notification->data['event']);
        $this->assertSame($designRequest->id, $notification->data['details']['design_request_id']);
        $this->actingAs($marketing)->post(route('prod03.task-pool.review', $designRequest), [
            'action' => 'revision',
            'reason' => 'Perlu penyesuaian visual.',
        ])->assertRedirect();
        $this->assertSame('REVISION_REQUIRED', $designRequest->fresh()->status);
        $this->assertSame($designer->id, $designRequest->fresh()->current_pic_id);

        $this->actingAs($designer)->post(route('prod03.task-pool.release', $designRequest), [
            'reason' => 'Tidak dapat melanjutkan pekerjaan.',
        ])->assertRedirect(route('prod03.task-pool.index'));
        $this->actingAs($replacement)->post(route('prod03.task-pool.take', $designRequest))->assertRedirect();
        $this->post(route('prod03.task-pool.submit-result', $designRequest), [
            'attachment' => UploadedFile::fake()->create('concept-v2.pdf', 1, 'application/pdf'),
        ])->assertRedirect();
        $this->actingAs($marketing)->post(route('prod03.task-pool.review', $designRequest), [
            'action' => 'accept',
        ])->assertRedirect();

        $this->assertSame('COMPLETED', $designRequest->fresh()->status);
        $this->assertSame([1, 2], $designRequest->attachments()->reorder('version')->pluck('version')->all());
        $this->assertCount(2, $designRequest->assignments);
        $this->assertDatabaseHas('production_workflow_events', [
            'design_request_id' => $designRequest->id,
            'event_type' => 'TASK_RELEASED',
            'actor_user_id' => $designer->id,
        ]);
        $this->assertDatabaseHas('production_workflow_events', [
            'design_request_id' => $designRequest->id,
            'event_type' => 'DESIGN_RESULT_ACCEPTED',
            'actor_user_id' => $marketing->id,
        ]);
    }

    public function test_noncritical_outstanding_task_requires_pic_deadline_and_impact_for_ready_with_notes(): void
    {
        $marketing = $this->createMarketing();
        $source = $this->createRecordedClientBrief($marketing);
        $brief = $this->createMarketingBrief($marketing, $source);
        $this->saveCompleteComponents($brief);
        $designer = $this->createUser('designer-notes@prod03.test');
        $this->grantRole($designer, 'DESIGNER');
        $designRequest = $this->createDesignRequest($marketing, $brief, 'NONCRITICAL');

        $this->actingAs($marketing)
            ->post(route('prod03.briefs.validate-readiness', $brief));
        $this->assertSame('DRAFT', $brief->fresh()->status);
        $this->actingAs($designer)->post(route('prod03.task-pool.take', $designRequest));
        $this->actingAs($marketing)
            ->post(route('prod03.briefs.validate-readiness', $brief));

        $this->assertSame('READY_WITH_NOTES', $brief->fresh()->status);
        $note = $brief->fresh()->readiness_snapshot['notes'][0];
        $this->assertSame($designRequest->id, $note['design_request_id']);
        $this->assertSame($designer->id, $note['pic_user_id']);
        $this->assertSame('Requires optional artwork for presentation', $note['impact']);
        $this->assertNotEmpty($note['deadline_at']);
    }

    public function test_cancellation_of_taken_task_waits_for_designer_acknowledgement(): void
    {
        $marketing = $this->createMarketing();
        $designer = $this->createUser('designer-cancel@prod03.test');
        $this->grantRole($designer, 'DESIGNER');
        $brief = $this->createReadyMarketingBrief($marketing, $this->createRecordedClientBrief($marketing));
        $designRequest = $this->createDesignRequest($marketing, $brief);
        $this->actingAs($designer)->post(route('prod03.task-pool.take', $designRequest));

        $this->actingAs($marketing)->post(route('prod03.design-requests.cancel', $designRequest), [
            'reason' => 'Klien menarik kebutuhan ini.',
        ])->assertRedirect();
        $this->assertSame('CANCELLATION_PENDING_ACK', $designRequest->fresh()->status);
        $this->assertSame(1, $designRequest->assignments()->whereNull('released_at')->count());

        $this->actingAs($designer)->post(route('prod03.task-pool.acknowledge-cancellation', $designRequest))
            ->assertRedirect();
        $this->assertSame('CANCELLED', $designRequest->fresh()->status);
        $this->assertSame(0, $designRequest->assignments()->whereNull('released_at')->count());
        $this->assertDatabaseHas('production_workflow_events', [
            'design_request_id' => $designRequest->id,
            'event_type' => 'CANCELLATION_ACKNOWLEDGED',
            'actor_role' => 'DESIGNER',
        ]);
    }

    public function test_available_design_request_can_be_cancelled_directly_with_reason(): void
    {
        $marketing = $this->createMarketing();
        $brief = $this->createReadyMarketingBrief($marketing, $this->createRecordedClientBrief($marketing));
        $designRequest = $this->createDesignRequest($marketing, $brief, 'CRITICAL');

        $this->actingAs($marketing)->post(route('prod03.design-requests.cancel', $designRequest), [
            'reason' => 'Klien menyediakan konsep pengganti.',
            'replacement_information' => 'Konsep tertulis diterima melalui email.',
            'replacement_source' => 'Email klien 21 Oktober 2026',
        ])->assertRedirect();

        $this->assertSame('CANCELLED', $designRequest->fresh()->status);
        $this->assertSame('Konsep tertulis diterima melalui email.', $designRequest->fresh()->replacement_information);
        $this->assertSame(0, $designRequest->assignments()->count());
        $this->assertDatabaseHas('production_workflow_events', [
            'design_request_id' => $designRequest->id,
            'event_type' => 'DESIGN_CANCELLED',
            'reason' => 'Klien menyediakan konsep pengganti.',
        ]);
    }

    public function test_marketing_classification_changes_are_reasoned_and_audited(): void
    {
        $marketing = $this->createMarketing();
        $brief = $this->createReadyMarketingBrief($marketing, $this->createRecordedClientBrief($marketing));
        $designRequest = $this->createDesignRequest($marketing, $brief, 'CRITICAL');

        $this->actingAs($marketing)->post(route('prod03.design-requests.classification', $designRequest), [
            'classification' => 'NONCRITICAL',
            'reason' => 'Informasi utama sudah tersedia dari klien.',
        ])->assertRedirect();

        $this->assertSame('NONCRITICAL', $designRequest->fresh()->classification);
        $this->assertDatabaseHas('production_workflow_events', [
            'design_request_id' => $designRequest->id,
            'event_type' => 'CLASSIFICATION_CHANGED',
            'reason' => 'Informasi utama sudah tersedia dari klien.',
        ]);
    }

    public function test_marketing_cannot_change_design_classification_after_brief_submission(): void
    {
        $marketing = $this->createMarketing();
        $brief = $this->createReadyMarketingBrief($marketing, $this->createRecordedClientBrief($marketing));
        $brief->status = 'SUBMITTED';
        $brief->save();
        $designRequest = $this->createDesignRequest($marketing, $brief, 'CRITICAL');

        $this->actingAs($marketing)
            ->post(route('prod03.design-requests.classification', $designRequest), [
                'classification' => 'NONCRITICAL',
                'reason' => 'Classification after submission.',
            ])
            ->assertSessionHasErrors('classification');

        $this->assertSame('CRITICAL', $designRequest->fresh()->classification);
        $this->assertDatabaseMissing('production_workflow_events', [
            'design_request_id' => $designRequest->id,
            'event_type' => 'CLASSIFICATION_CHANGED',
        ]);
    }

    public function test_designer_may_request_deadline_change_but_only_marketing_decision_changes_deadline(): void
    {
        $marketing = $this->createMarketing();
        $designer = $this->createUser('designer-deadline@prod03.test');
        $this->grantRole($designer, 'DESIGNER');
        $brief = $this->createReadyMarketingBrief($marketing, $this->createRecordedClientBrief($marketing));
        $designRequest = $this->createDesignRequest($marketing, $brief);
        $originalDeadline = $designRequest->deadline_at->toISOString();
        $this->actingAs($designer)->post(route('prod03.task-pool.take', $designRequest));

        $this->post(route('prod03.task-pool.deadline-change', $designRequest), [
            'requested_deadline_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'reason' => 'Perlu satu putaran review tambahan.',
        ])->assertRedirect();
        $change = $designRequest->deadlineChanges()->sole();
        $this->assertSame('PENDING', $change->status);
        $this->assertSame($originalDeadline, $designRequest->fresh()->deadline_at->toISOString());

        $this->actingAs($marketing)->post(
            route('prod03.design-requests.deadline-decision', [$designRequest, $change]),
            ['decision' => 'approve', 'decision_reason' => 'Jadwal disetujui.'],
        )->assertRedirect();
        $this->assertSame('APPROVED', $change->fresh()->status);
        $this->assertEquals($change->requested_deadline_at, $designRequest->fresh()->deadline_at);
        $this->assertDatabaseHas('production_workflow_events', [
            'design_request_id' => $designRequest->id,
            'event_type' => 'DEADLINE_CHANGE_DECIDED',
            'actor_role' => 'MARKETING',
        ]);
    }

    public function test_deadline_and_cancellation_reminders_are_sent_once_to_the_intended_users(): void
    {
        $marketing = $this->createMarketing();
        $designer = $this->createUser('designer-reminders@prod03.test');
        $this->grantRole($designer, 'DESIGNER');
        $brief = $this->createReadyMarketingBrief($marketing, $this->createRecordedClientBrief($marketing));

        $dueSoon = $this->createDesignRequest($marketing, $brief, 'NONCRITICAL');
        $dueSoon->forceFill([
            'status' => 'IN_PROGRESS',
            'current_pic_id' => $designer->id,
            'deadline_at' => now()->addHours(12),
        ])->save();

        $awaitingAcknowledgement = $this->createDesignRequest($marketing, $brief, 'NONCRITICAL');
        $awaitingAcknowledgement->forceFill([
            'status' => 'CANCELLATION_PENDING_ACK',
            'current_pic_id' => $designer->id,
            'cancellation_requested_at' => now()->subHours(25),
        ])->save();

        Artisan::call('prod03:send-design-reminders');

        $designerEvents = $designer->notifications()->get()->pluck('data.event')->all();
        $marketingEvents = $marketing->notifications()->get()->pluck('data.event')->all();
        $this->assertContains('DEADLINE_REMINDER', $designerEvents);
        $this->assertContains('CANCELLATION_REMINDER', $designerEvents);
        $this->assertContains('DEADLINE_REMINDER', $marketingEvents);
        $this->assertNotNull($dueSoon->fresh()->deadline_reminder_sent_at);
        $this->assertNotNull($awaitingAcknowledgement->fresh()->cancellation_reminder_sent_at);
        $notificationCount = DB::table('notifications')->count();

        Artisan::call('prod03:send-design-reminders');

        $this->assertSame($notificationCount, DB::table('notifications')->count());
    }

    public function test_competing_take_requests_cannot_create_two_active_pic_assignments(): void
    {
        $marketing = $this->createMarketing();
        $firstDesigner = $this->createUser('designer-race-one@prod03.test');
        $secondDesigner = $this->createUser('designer-race-two@prod03.test');
        $this->grantRole($firstDesigner, 'DESIGNER');
        $this->grantRole($secondDesigner, 'DESIGNER');
        $brief = $this->createReadyMarketingBrief($marketing, $this->createRecordedClientBrief($marketing));
        $designRequest = $this->createDesignRequest($marketing, $brief);

        $this->actingAs($firstDesigner)->post(route('prod03.task-pool.take', $designRequest))->assertRedirect();
        $this->actingAs($secondDesigner)->post(route('prod03.task-pool.take', $designRequest))->assertSessionHasErrors('task');

        $this->assertSame(1, $designRequest->assignments()->whereNull('released_at')->count());
        $this->assertSame($firstDesigner->id, $designRequest->fresh()->current_pic_id);
    }

    public function test_postgresql_lock_serializes_two_concurrent_task_pool_claims(): void
    {
        if (! function_exists('pg_connect') || ! function_exists('pg_send_query')) {
            $this->markTestSkipped('Concurrent claim test requires PostgreSQL async connections.');
        }

        $config = config('database.connections.pgsql');
        $dsn = collect([
            'host' => (string) $config['host'],
            'port' => (string) $config['port'],
            'dbname' => (string) $config['database'],
            'user' => (string) $config['username'],
            'password' => (string) $config['password'],
        ])->map(fn (string $value, string $key) => $key."='".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'")->implode(' ');
        $first = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        $second = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        $observer = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        $fixtureIds = null;
        $firstOpen = false;
        $secondOpen = false;
        $secondPending = false;

        try {
            $this->assertNotFalse($first);
            $this->assertNotFalse($second);
            $this->assertNotFalse($observer);
            foreach ([$first, $second, $observer] as $connection) {
                $identity = pg_query($connection, 'select current_database() database, current_user role, session_user session_role');
                $row = pg_fetch_assoc($identity);
                $this->assertSame('enterprise_bpm_test', $row['database']);
                $this->assertSame('enterprise_bpm_test_user', $row['role']);
                $this->assertSame('enterprise_bpm_test_user', $row['session_role']);
            }

            $fixtureIds = $this->createCommittedConcurrentFixture($first);
            $firstPid = (int) pg_fetch_result(pg_query($first, 'select pg_backend_pid()'), 0, 0);
            $secondPid = (int) pg_fetch_result(pg_query($second, 'select pg_backend_pid()'), 0, 0);
            $this->assertNotSame($firstPid, $secondPid);

            $this->assertNotFalse(pg_query($first, 'begin'));
            $firstOpen = true;
            $locked = pg_query_params($first, 'select id from design_requests where id = $1 for update', [$fixtureIds['design_request']]);
            $this->assertNotFalse($locked);

            $this->assertNotFalse(pg_query($second, 'begin'));
            $secondOpen = true;
            $this->assertTrue(pg_send_query(
                $second,
                "update design_requests set status = 'IN_PROGRESS', current_pic_id = {$fixtureIds['second_designer']} where id = {$fixtureIds['design_request']} and status = 'AVAILABLE' returning id"
            ));
            $secondPending = true;
            $this->waitUntilBlocked($observer, $secondPid, $firstPid);

            $this->assertNotFalse(pg_query_params(
                $first,
                "update design_requests set status = 'IN_PROGRESS', current_pic_id = $1 where id = $2 and status = 'AVAILABLE'",
                [$fixtureIds['first_designer'], $fixtureIds['design_request']],
            ));
            $this->assertNotFalse(pg_query_params(
                $first,
                'insert into design_request_assignments (design_request_id, designer_user_id, assigned_at, created_at, updated_at) values ($1, $2, now(), now(), now())',
                [$fixtureIds['design_request'], $fixtureIds['first_designer']],
            ));
            $this->assertNotFalse(pg_query($first, 'commit'));
            $firstOpen = false;

            $claim = pg_get_result($second);
            $secondPending = false;
            $this->assertNotFalse($claim);
            $this->assertSame(PGSQL_TUPLES_OK, pg_result_status($claim));
            $this->assertSame(0, pg_num_rows($claim));
            $this->assertNotFalse(pg_query($second, 'commit'));
            $secondOpen = false;

            $state = pg_query_params($observer, 'select current_pic_id, (select count(*) from design_request_assignments where design_request_id = $1 and released_at is null) active_assignments from design_requests where id = $1', [$fixtureIds['design_request']]);
            $stateRow = pg_fetch_assoc($state);
            $this->assertSame($fixtureIds['first_designer'], (int) $stateRow['current_pic_id']);
            $this->assertSame('1', $stateRow['active_assignments']);
        } finally {
            if ($secondPending && pg_connection_busy($second)) {
                pg_cancel_query($second);
                while (pg_get_result($second) !== false) {
                }
            }
            if ($secondOpen) {
                pg_query($second, 'rollback');
            }
            if ($firstOpen) {
                pg_query($first, 'rollback');
            }
            if (is_array($fixtureIds) && $observer instanceof Connection) {
                $this->removeCommittedConcurrentFixture($observer, $fixtureIds);
            }
            foreach ([$first, $second, $observer] as $connection) {
                if ($connection instanceof Connection) {
                    pg_close($connection);
                }
            }
        }
    }

    private function createMarketing(): User
    {
        $user = $this->createUser('marketing-'.uniqid('', true).'@prod03.test');
        $this->grantRole($user, 'MARKETING');

        return $user;
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'name' => 'PROD-03 Tester',
            'email' => $email,
            'password' => Hash::make('prod03-test-password'),
        ]);
    }

    private function grantRole(User $user, string $code): void
    {
        $roleId = DB::table('bpm_roles')->where('code', $code)->value('id');
        if ($roleId === null) {
            $roleId = DB::table('bpm_roles')->insertGetId([
                'code' => $code,
                'name' => ucfirst(strtolower($code)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $user->roles()->attach($roleId);
    }

    private function createClientBrief(User $marketing, string $status): ClientBrief
    {
        return ClientBrief::query()->forceCreate([
            'request_number' => $status === 'RECORDED' ? 'REQ-'.uniqid() : null,
            'status' => $status,
            'client_name' => 'Client PROD-03',
            'communication_source' => 'Email',
            'requirement_type' => 'Exhibition',
            'description' => 'Design brief source',
            'marketing_user_id' => $marketing->id,
            'recorded_at' => $status === 'RECORDED' ? now() : null,
        ]);
    }

    private function createRecordedClientBrief(User $marketing): ClientBrief
    {
        return $this->createClientBrief($marketing, 'RECORDED');
    }

    private function createMarketingBrief(User $marketing, ClientBrief $source): MarketingBrief
    {
        return MarketingBrief::query()->create([
            'client_brief_id' => $source->id,
            'marketing_user_id' => $marketing->id,
            'status' => 'DRAFT',
        ]);
    }

    private function createReadyMarketingBrief(User $marketing, ClientBrief $source): MarketingBrief
    {
        $brief = $this->createMarketingBrief($marketing, $source);
        $this->saveCompleteComponents($brief);
        $brief->status = 'READY_FOR_SUBMISSION';
        $brief->save();

        return $brief;
    }

    private function completeComponents(): array
    {
        return [
            'work_type' => ['value' => 'Exhibition booth'],
            'work_concept' => ['value' => 'Open concept'],
            'visualization' => ['value' => '3D visualization'],
            'work_specification' => ['value' => 'Modular materials'],
            'duration' => ['value' => 'Three days'],
            'quantity' => ['value' => 'One unit'],
        ];
    }

    private function saveCompleteComponents(MarketingBrief $brief): void
    {
        foreach ($this->completeComponents() as $key => $data) {
            $brief->components()->create([
                'component' => $key,
                'value' => $data['value'],
                'is_provisional' => false,
            ]);
        }
    }

    private function createDesignRequest(User $marketing, MarketingBrief $brief, string $classification = 'CRITICAL'): DesignRequest
    {
        return $brief->designRequests()->create([
            'created_by' => $marketing->id,
            'title' => 'Concept artwork',
            'description' => 'Create concept artwork for the brief.',
            'classification' => $classification,
            'classification_reason' => 'Impact on clarity recorded by Marketing.',
            'impact' => 'Requires optional artwork for presentation',
            'status' => 'AVAILABLE',
            'deadline_at' => now()->addDays(2),
        ]);
    }

    /**
     * @return array{design_request: int, first_designer: int, second_designer: int}
     */
    private function createCommittedConcurrentFixture(Connection $connection): array
    {
        $marketingEmail = 'prod03-race-marketing-'.bin2hex(random_bytes(6)).'@example.test';
        $firstEmail = 'prod03-race-one-'.bin2hex(random_bytes(6)).'@example.test';
        $secondEmail = 'prod03-race-two-'.bin2hex(random_bytes(6)).'@example.test';
        $password = Hash::make('temporary-test-only');
        $marketing = $this->insertConcurrentUser($connection, $marketingEmail, $password);
        $firstDesigner = $this->insertConcurrentUser($connection, $firstEmail, $password);
        $secondDesigner = $this->insertConcurrentUser($connection, $secondEmail, $password);
        $this->ensureConcurrentRole($connection, 'MARKETING', 'Marketing', $marketing);
        $this->ensureConcurrentRole($connection, 'DESIGNER', 'Designer', $firstDesigner);
        $this->ensureConcurrentRole($connection, 'DESIGNER', 'Designer', $secondDesigner);

        $source = pg_query_params(
            $connection,
            "insert into client_briefs (request_number, status, client_name, communication_source, requirement_type, description, marketing_user_id, recorded_at, created_at, updated_at) values ($1, 'RECORDED', 'Concurrent client', 'Email', 'Display', 'Test source', $2, now(), now(), now()) returning id",
            ['REQ-'.bin2hex(random_bytes(8)), $marketing],
        );
        $sourceId = (int) pg_fetch_result($source, 0, 'id');
        $brief = pg_query_params(
            $connection,
            "insert into marketing_briefs (client_brief_id, marketing_user_id, status, created_at, updated_at) values ($1, $2, 'DRAFT', now(), now()) returning id",
            [$sourceId, $marketing],
        );
        $briefId = (int) pg_fetch_result($brief, 0, 'id');
        $task = pg_query_params(
            $connection,
            "insert into design_requests (marketing_brief_id, created_by, title, description, classification, classification_reason, impact, status, deadline_at, created_at, updated_at) values ($1, $2, 'Concurrent task', 'Concurrent take test', 'NONCRITICAL', 'Test', 'Test', 'AVAILABLE', now() + interval '1 day', now(), now()) returning id",
            [$briefId, $marketing],
        );

        return [
            'design_request' => (int) pg_fetch_result($task, 0, 'id'),
            'first_designer' => $firstDesigner,
            'second_designer' => $secondDesigner,
            'marketing' => $marketing,
            'client_brief' => $sourceId,
            'marketing_brief' => $briefId,
            'users' => [$marketing, $firstDesigner, $secondDesigner],
        ];
    }

    private function insertConcurrentUser(Connection $connection, string $email, string $password): int
    {
        $result = pg_query_params(
            $connection,
            'insert into users (name, email, password, created_at, updated_at) values ($1, $2, $3, now(), now()) returning id',
            ['PROD-03 concurrent test', $email, $password],
        );

        return (int) pg_fetch_result($result, 0, 'id');
    }

    private function ensureConcurrentRole(Connection $connection, string $code, string $name, int $userId): void
    {
        pg_query_params(
            $connection,
            'insert into bpm_roles (code, name, created_at, updated_at) values ($1, $2, now(), now()) on conflict (code) do nothing',
            [$code, $name],
        );
        $role = pg_query_params($connection, 'select id from bpm_roles where code = $1', [$code]);
        $roleId = (int) pg_fetch_result($role, 0, 'id');
        pg_query_params($connection, 'insert into bpm_role_user (user_id, bpm_role_id) values ($1, $2) on conflict do nothing', [$userId, $roleId]);
    }

    private function waitUntilBlocked(Connection $observer, int $blockedPid, int $blockingPid): void
    {
        $deadline = hrtime(true) + 5_000_000_000;
        do {
            $result = pg_query_params(
                $observer,
                'select $1 = any(pg_blocking_pids($2)) blocked from pg_stat_activity where pid = $2',
                [$blockingPid, $blockedPid],
            );
            $row = pg_fetch_assoc($result);
            if ($row !== false && $row['blocked'] === 't') {
                return;
            }
            usleep(10_000);
        } while (hrtime(true) < $deadline);

        $this->fail('Competing task claim did not block on the Design Request row lock.');
    }

    /**
     * @param  array<string, mixed>  $ids
     */
    private function removeCommittedConcurrentFixture(Connection $connection, array $ids): void
    {
        pg_query_params($connection, 'delete from design_request_assignments where design_request_id = $1', [$ids['design_request']]);
        pg_query_params($connection, 'delete from production_workflow_events where marketing_brief_id = $1', [$ids['marketing_brief']]);
        pg_query_params($connection, 'delete from design_requests where id = $1', [$ids['design_request']]);
        pg_query_params($connection, 'delete from marketing_briefs where id = $1', [$ids['marketing_brief']]);
        pg_query_params($connection, 'delete from client_brief_events where client_brief_id = $1', [$ids['client_brief']]);
        pg_query_params($connection, 'delete from client_briefs where id = $1', [$ids['client_brief']]);
        pg_query_params($connection, 'delete from bpm_role_user where user_id = any($1::bigint[])', ['{'.implode(',', $ids['users']).'}']);
        pg_query_params($connection, 'delete from users where id = any($1::bigint[])', ['{'.implode(',', $ids['users']).'}']);
    }
}
