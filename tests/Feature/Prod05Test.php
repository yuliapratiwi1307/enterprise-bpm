<?php

namespace Tests\Feature;

use App\Models\MarketingBrief;
use App\Models\Prod04BriefVersion;
use App\Models\Prod05ActivityControl;
use App\Models\Prod05RevisionClassification;
use App\Models\Prod05Validation;
use App\Models\Prod05ValidationEvent;
use App\Models\User;
use App\Notifications\ProductionWorkflowNotification;
use App\Services\Prod04Workflow;
use App\Services\Prod05ValidationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class Prod05Test extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('PROD-05 tests require APP_ENV=testing.');
        }

        $identity = DB::selectOne('select current_database() as database, current_user as username');
        if (
            $identity?->database !== 'enterprise_bpm_test'
            || $identity?->username !== 'enterprise_bpm_test_user'
        ) {
            throw new RuntimeException('PROD-05 tests are restricted to enterprise_bpm_test_user on enterprise_bpm_test.');
        }
    }

    public function test_only_assistant_manager_can_start_validation(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $otherMarketing = $this->createUserWithRole('other-marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        // Non-ASST_MANAGER cannot start validation
        $this->actingAs($otherMarketing)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertForbidden();

        // ASST_MANAGER can start validation
        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $this->assertDatabaseHas('prod05_validations', [
            'brief_version_id' => $version->id,
            'status' => 'UNDER_REVIEW',
        ]);
    }

    public function test_validation_start_creates_automatic_acknowledgement(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $this->assertDatabaseCount('prod04_acknowledgements', 0);

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $this->assertDatabaseCount('prod04_acknowledgements', 1);
        $this->assertSame('ACKNOWLEDGED', $version->fresh()->distribution_status);
    }

    public function test_validation_result_transitions(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $validation = Prod05Validation::query()->sole();

        // Give VALIDATED result
        $this->actingAs($assistantManager)
            ->post(route('prod05.result', $validation), [
                'status' => 'VALIDATED',
                'reason' => 'Informasi lengkap dan memenuhi kriteria.',
            ])
            ->assertRedirect();

        $this->assertSame('VALIDATED', $validation->fresh()->status);
        $this->assertDatabaseHas('prod05_validation_events', [
            'validation_id' => $validation->id,
            'event_type' => 'VALIDATION_RESULT_GIVEN',
        ]);
    }

    public function test_validated_with_notes_stores_provisional_data(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $validation = Prod05Validation::query()->sole();

        $this->actingAs($assistantManager)
            ->post(route('prod05.result', $validation), [
                'status' => 'VALIDATED_WITH_NOTES',
                'reason' => 'Informasi kritis cukup, estimasi sementara diterima.',
                'provisional_data' => [
                    'quantity' => ['value' => 'Estimasi 100 unit', 'source' => 'Peringkat awal', 'limitation' => 'Belum final'],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('VALIDATED_WITH_NOTES', $validation->fresh()->status);
        $this->assertNotNull($validation->fresh()->provisional_data);
        $this->assertArrayHasKey('quantity', $validation->fresh()->provisional_data);
    }

    public function test_returned_brief_preserves_immutable_version(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();
        $snapshot = $version->snapshot;

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $validation = Prod05Validation::query()->sole();

        $this->actingAs($assistantManager)
            ->post(route('prod05.result', $validation), [
                'status' => 'RETURNED',
                'reason' => 'Informasi kuantitas tidak lengkap.',
                'return_field' => 'quantity',
            ])
            ->assertRedirect();

        $this->assertSame('RETURNED', $validation->fresh()->status);
        // PROD-04 snapshot must remain immutable
        $this->assertSame($snapshot, $version->fresh()->snapshot);
        $this->assertDatabaseHas('prod05_validation_events', [
            'validation_id' => $validation->id,
            'event_type' => 'VALIDATION_RESULT_GIVEN',
            'after_status' => 'RETURNED',
        ]);
    }

    public function test_manager_decision_requires_manager_role(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $this->createUserWithRole('manager-produksi@prod05.test', 'MANAGER_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $validation = Prod05Validation::query()->sole();

        // Set to PENDING_MANAGER first
        $this->actingAs($assistantManager)
            ->post(route('prod05.result', $validation), [
                'status' => 'PENDING_MANAGER',
                'reason' => 'Perlu keputusan Manager tentang data Provisional.',
            ])
            ->assertRedirect();

        // ASST_MANAGER cannot make manager decision
        $this->actingAs($assistantManager)
            ->post(route('prod05.manager-decision', $validation), [
                'decision' => 'APPROVED_FOR_REVIEW',
                'reason' => 'Disetujui.',
            ])
            ->assertForbidden();

        // MANAGER_PRODUKSI can make decision
        $manager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($manager)
            ->post(route('prod05.manager-decision', $validation), [
                'decision' => 'APPROVED_FOR_REVIEW',
                'reason' => 'Data Provisional cukup untuk kajian awal.',
            ])
            ->assertRedirect();

        $this->assertSame('APPROVED_FOR_REVIEW', $validation->fresh()->status);
        $this->assertSame('APPROVED_FOR_REVIEW', $validation->fresh()->manager_decision);
    }

    public function test_impact_classification_minor_major_critical(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $validation = Prod05Validation::query()->sole();

        // Classify as CRITICAL
        $this->actingAs($assistantManager)
            ->post(route('prod05.classify', $validation), [
                'classification' => 'CRITICAL',
                'reason' => 'Perubahan kuantitas berdampak pada keamanan struktur.',
            ])
            ->assertRedirect();

        $this->assertSame('CRITICAL', $validation->fresh()->impact_classification);
        $this->assertSame('UNDER_REVIEW', $validation->fresh()->status);
        $this->assertDatabaseCount('prod05_revision_classifications', 1);
    }

    public function test_critical_downgrade_requires_manager_approval(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $this->createUserWithRole('manager-produksi@prod05.test', 'MANAGER_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();
        $this->actingAs($assistantManager)
            ->post(route('prod05.classify', $validation = Prod05Validation::query()->sole()), [
                'classification' => 'CRITICAL',
                'reason' => 'Dampak kritis.',
            ])
            ->assertRedirect();

        // ASST_MANAGER cannot downgrade from CRITICAL without manager approval
        $this->actingAs($assistantManager)
            ->post(route('prod05.classify', $validation), [
                'classification' => 'MAJOR',
                'reason' => 'Revised assessment.',
                'override_recommendation' => true,
            ])
            ->assertForbidden();

        // Count should remain 1 (the CRITICAL classification)
        $this->assertDatabaseCount('prod05_revision_classifications', 1);
    }

    public function test_append_only_events_cannot_be_modified(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $validation = Prod05Validation::query()->sole();
        $event = Prod05ValidationEvent::query()->first();

        try {
            DB::transaction(fn () => DB::table('prod05_validation_events')->where('id', $event->id)->update([
                'event_type' => 'TAMPERED',
            ]));
            $this->fail('PROD-05 validation events must reject updates.');
        } catch (QueryException) {
            // Expected - append-only trigger
        }

        $this->assertNotSame('TAMPERED', $event->fresh()->event_type);
    }

    public function test_no_duplicate_validation_records_on_retry(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $this->assertSame(1, Prod05Validation::query()->count());
    }

    public function test_level3_blocked_when_critical_unresolved(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('marketing@prod05.test', 'MARKETING');
        $this->createUserWithRole('assistant-manager@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('production-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);

        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();

        $assistantManager = User::query()->whereHas('roles', fn ($q) => $q->where('code', 'ASST_MANAGER_PRODUKSI'))->firstOrFail();
        $this->actingAs($assistantManager)
            ->post(route('prod05.start', ['version' => $version]))
            ->assertRedirect();

        $validation = Prod05Validation::query()->sole();

        // Create activity control for Level 3 activity
        $activity = Prod05ActivityControl::query()->create([
            'validation_id' => $validation->id,
            'activity_key' => 'PRODUCTION_LINE_01',
            'activity_type' => 'PHYSICAL_PRODUCTION',
            'control_status' => 'ACTIVE',
        ]);

        $service = app(Prod05ValidationService::class);
        $this->assertTrue($service->isLevel3Blocked($activity));

        // Classify as CRITICAL
        $this->actingAs($assistantManager)
            ->post(route('prod05.classify', $validation), [
                'classification' => 'CRITICAL',
                'reason' => 'Dampak kritis pada produksi fisik.',
            ])
            ->assertRedirect();

        $this->assertTrue($service->isLevel3Blocked($activity->fresh()));
    }

    public function test_f15e_critical_hold_scopes_known_dependencies(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('f15e-marketing@prod05.test', 'MARKETING');
        $assistant = $this->createUserWithRole('f15e-assistant@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('f15e-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);
        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();
        $this->actingAs($assistant)->post(route('prod05.start', ['version' => $version]))->assertRedirect();
        $validation = Prod05Validation::query()->sole();
        $service = app(Prod05ValidationService::class);
        $source = $service->createActivityControl($validation, 'DESIGN', 'DESIGN', ['PRINTING']);
        $print = $service->createActivityControl($validation, 'PRINTING', 'PHYSICAL_PRODUCTION');
        $other = $service->createActivityControl($validation, 'ACCOUNTING', 'ADMIN');
        $this->actingAs($assistant)->post(route('prod05.classify', $validation), [
            'classification' => 'CRITICAL', 'reason' => 'Revisi desain',
            'affected_activity_keys' => ['DESIGN'],
        ])->assertRedirect();
        $this->assertSame('ON_HOLD', $source->fresh()->control_status);
        $this->assertSame('ON_HOLD', $print->fresh()->control_status);
        $this->assertSame('ACTIVE', $other->fresh()->control_status);
        $this->assertTrue($service->isLevel3Blocked($print->fresh()));
    }

    public function test_f15e_unknown_impact_scope_fails_closed(): void
    {
        $this->configureCalendar();
        Notification::fake();
        $marketing = $this->createUserWithRole('f15e2-marketing@prod05.test', 'MARKETING');
        $assistant = $this->createUserWithRole('f15e2-assistant@prod05.test', 'ASST_MANAGER_PRODUKSI');
        $this->createUserWithRole('f15e2-admin@prod05.test', 'ADMIN_PRODUKSI');
        $brief = $this->createReadyBrief($marketing);
        $this->actingAs($marketing)->post(route('prod04.submit', $brief))->assertRedirect();
        $version = Prod04BriefVersion::query()->sole();
        $this->actingAs($assistant)->post(route('prod05.start', ['version' => $version]))->assertRedirect();
        $validation = Prod05Validation::query()->sole();
        $service = app(Prod05ValidationService::class);
        $first = $service->createActivityControl($validation, 'A', 'DESIGN');
        $second = $service->createActivityControl($validation, 'B', 'ADMIN');
        $this->actingAs($assistant)->post(route('prod05.classify', $validation), [
            'classification' => 'CRITICAL', 'reason' => 'Cakupan tidak diketahui',
            'affected_activity_keys' => ['UNKNOWN'],
        ])->assertRedirect();
        $this->assertSame('ON_HOLD', $first->fresh()->control_status);
        $this->assertSame('ON_HOLD', $second->fresh()->control_status);
    }

    private function createUserWithRole(string $email, string $roleCode): User
    {
        $user = User::create([
            'name' => str_replace('_', ' ', $roleCode) . ' Test',
            'email' => $email,
            'password' => bcrypt('Prod05TestOnlyPassword!'),
        ]);
        $roleId = DB::table('bpm_roles')->where('code', $roleCode)->value('id');
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
            'client_name' => 'PROD-05 Test Client',
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

    private function configureCalendar(?array $calendar = null): void
    {
        config([
            'prod05.working_calendar' => $calendar ?? [
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
