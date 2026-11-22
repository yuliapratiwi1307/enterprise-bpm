<?php

namespace Tests\Feature;

use App\Models\ClientBrief;
use App\Models\ClientBriefEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PgSql\Connection;
use Tests\TestCase;

class Prod02Test extends TestCase
{
    use RefreshDatabase;

    public function test_application_migrations_create_laravel_and_prod02_tables(): void
    {
        foreach ([
            'users',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'bpm_roles',
            'bpm_role_user',
            'client_briefs',
            'client_brief_events',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected {$table} table after application migrations.");
        }
    }

    public function test_marketing_role_can_access_prod02_and_other_roles_are_denied(): void
    {
        $marketingUser = $this->createUser('marketing@example.test');
        $this->grantMarketingRole($marketingUser);

        $this->actingAs($marketingUser)
            ->get(route('prod02.index'))
            ->assertOk();

        $this->get(route('prod02.create'))->assertOk();

        $otherUser = $this->createUser('other@example.test');

        $this->actingAs($otherUser)
            ->get(route('prod02.index'))
            ->assertForbidden();

        $this->get(route('prod02.create'))->assertForbidden();
    }

    public function test_marketing_user_cannot_view_or_update_another_marketing_users_draft(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherMarketingUser = $this->createUser('other-marketing@example.test');
        $this->grantMarketingRole($owner);
        $this->grantMarketingRole($otherMarketingUser);

        $brief = $this->createDraft($owner, [
            'client_name' => 'Owner client',
            'communication_source' => 'Email',
        ]);

        $this->actingAs($otherMarketingUser)
            ->get(route('prod02.edit', $brief))
            ->assertForbidden();

        $this->put(route('prod02.update', $brief), [
            'client_name' => 'Changed by another user',
        ])->assertForbidden();

        $this->assertDatabaseHas('client_briefs', [
            'id' => $brief->id,
            'client_name' => 'Owner client',
        ]);
        $this->assertDatabaseCount('client_brief_events', 1);
    }

    public function test_marketing_can_create_partial_draft_with_incomplete_status_and_created_event(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);

        $this->actingAs($user)
            ->post(route('prod02.store'), [
                'client_name' => 'Contoso',
                'communication_source' => 'WhatsApp',
            ])
            ->assertRedirect();

        $brief = ClientBrief::query()->sole();

        $this->assertSame('DRAFT', $brief->status);
        $this->assertFalse($brief->is_complete);
        $this->assertDatabaseHas('client_brief_events', [
            'client_brief_id' => $brief->id,
            'event_type' => 'DRAFT_CREATED',
            'actor_user_id' => $user->id,
            'actor_role' => 'MARKETING',
        ]);

        $event = $brief->events()->sole();
        $this->assertNull($event->before_data);
        $this->assertSame('DRAFT', $event->after_data['status']);
        $this->assertSame('Contoso', $event->after_data['client_name']);
        $this->assertNotNull($event->occurred_at);
    }

    public function test_complete_required_fields_are_marked_complete_but_remain_a_draft(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);

        $this->actingAs($user)
            ->post(route('prod02.store'), [
                'client_name' => 'Contoso',
                'communication_source' => 'Meeting',
                'requirement_type' => 'Consultation',
                'description' => 'Initial request',
            ])
            ->assertRedirect();

        $brief = ClientBrief::query()->sole();

        $this->assertSame('DRAFT', $brief->status);
        $this->assertTrue($brief->is_complete);
    }

    public function test_updating_draft_preserves_status_and_records_before_and_after_data(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $brief = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'communication_source' => 'WhatsApp',
            'requirement_type' => 'Consultation',
            'description' => 'Original request',
        ]);

        $this->actingAs($user)
            ->put(route('prod02.update', $brief), [
                'client_name' => 'Contoso Updated',
                'communication_source' => 'Email',
                'requirement_type' => 'Consultation',
                'description' => 'Updated request',
            ])
            ->assertRedirect(route('prod02.edit', $brief));

        $brief->refresh();
        $this->assertSame('DRAFT', $brief->status);
        $this->assertTrue($brief->is_complete);
        $this->assertSame('Contoso Updated', $brief->client_name);
        $this->assertCount(2, $brief->events);

        $createdEvent = $brief->events()->where('event_type', 'DRAFT_CREATED')->sole();
        $updatedEvent = $brief->events()->where('event_type', 'DRAFT_UPDATED')->sole();

        $this->assertSame('Contoso', $createdEvent->after_data['client_name']);
        $this->assertSame('Contoso', $updatedEvent->before_data['client_name']);
        $this->assertSame('Contoso Updated', $updatedEvent->after_data['client_name']);
        $this->assertSame('WhatsApp', $updatedEvent->before_data['communication_source']);
        $this->assertSame('Email', $updatedEvent->after_data['communication_source']);
        $this->assertSame($user->id, $updatedEvent->actor_user_id);
        $this->assertSame('MARKETING', $updatedEvent->actor_role);
    }

    public function test_communication_source_validation_matches_form_options(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, []);
        $this->actingAs($user);

        $this->get(route('prod02.edit', ['brief' => $draft->id, 'step' => 2]))
            ->assertOk()
            ->assertSee('WhatsApp')
            ->assertSee('Telepon')
            ->assertSee('Email')
            ->assertSee('Meeting')
            ->assertSee('Lainnya');

        $this->from(route('prod02.create'))
            ->post(route('prod02.store'), [
                'communication_source' => 'Unlisted source',
            ])
            ->assertRedirect(route('prod02.create'))
            ->assertSessionHasErrors('communication_source');

        $this->assertDatabaseCount('client_briefs', 1);
        $this->assertDatabaseCount('client_brief_events', 1);
    }

    public function test_all_communication_source_options_are_accepted_by_server_validation(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $this->actingAs($user);

        foreach (['WhatsApp', 'Telepon', 'Email', 'Meeting', 'Lainnya'] as $source) {
            $this->post(route('prod02.store'), [
                'client_name' => 'Client '.$source,
                'communication_source' => $source,
            ])->assertRedirect();
        }

        $this->assertDatabaseCount('client_briefs', 5);
        $this->assertDatabaseCount('client_brief_events', 5);
    }

    public function test_prod02_routes_are_registered_once_with_expected_methods_and_uris(): void
    {
        $expectedRoutes = [
            'prod02.index' => ['GET', 'production/briefs'],
            'prod02.create' => ['GET', 'production/briefs/create'],
            'prod02.store' => ['POST', 'production/briefs'],
            'prod02.submit' => ['POST', 'production/briefs/{brief}/submit'],
            'prod02.show' => ['GET', 'production/briefs/{brief}'],
            'prod02.edit' => ['GET', 'production/briefs/{brief}/edit'],
            'prod02.update' => ['PUT', 'production/briefs/{brief}'],
            'prod02.duplicate-check' => ['POST', 'production/briefs/{brief}/duplicate-check'],
            'prod02.duplicate-decision' => ['POST', 'production/briefs/{brief}/duplicate-decision'],
        ];

        foreach ($expectedRoutes as $name => [$method, $uri]) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertNotNull($route, "Expected named route {$name}.");
            $this->assertSame($uri, $route->uri());
            $this->assertContains($method, $route->methods());
            $this->assertContains('auth', $route->gatherMiddleware());
        }

        $snippet = file_get_contents(base_path('routes/prod02_routes_snippet.php'));
        $this->assertIsString($snippet);
        $this->assertStringNotContainsString('Route::', $snippet);
    }

    public function test_duplicate_check_uses_normalized_fields_and_records_explainable_candidates(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => '  ACME, Corporation ',
            'communication_source' => 'Email',
            'requirement_type' => 'Office Renovation',
            'work_location' => 'Jakarta Selatan',
            'target_date' => '2026-11-03',
            'description' => 'Renovate office reception and meeting rooms',
        ]);
        $recorded = $this->createRecordedBrief($user, [
            'client_name' => 'Acme Corporation',
            'requirement_type' => 'Office Renovation',
            'work_location' => 'Jakarta Selatan',
            'target_date' => '2026-11-28',
            'description' => 'Renovation of meeting rooms and office reception',
        ]);

        $response = $this->actingAs($user)
            ->post(route('prod02.duplicate-check', $draft))
            ->assertOk()
            ->assertSee('Kandidat RECORDED')
            ->assertSee('Klien: 100%')
            ->assertSee('Jenis pekerjaan: 100%')
            ->assertSee('Permintaan Baru')
            ->assertSee('Kelanjutan')
            ->assertSee('Duplikat');

        $event = $draft->events()->where('event_type', 'DUPLICATE_CHECKED')->sole();
        $this->assertSame('prod02-duplicate-v1', $event->after_data['algorithm_version']);
        $this->assertSame($draft->status, 'DRAFT');
        $this->assertSame($recorded->id, $event->after_data['candidates'][0]['id']);
        $this->assertContains('target_date', array_column($event->after_data['candidates'][0]['reasons'], 'field'));
        $this->assertDatabaseCount('client_brief_events', 2);
    }

    public function test_empty_optional_fields_do_not_count_as_matching_signals(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'communication_source' => 'Email',
        ]);
        $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
        ]);

        $this->actingAs($user)
            ->post(route('prod02.duplicate-check', $draft))
            ->assertOk()
            ->assertSee('Tidak ditemukan kandidat');

        $event = $draft->events()->where('event_type', 'DUPLICATE_CHECKED')->sole();
        $this->assertSame([], $event->after_data['candidates']);
    }

    public function test_duplicate_checker_returns_stably_sorted_maximum_ten_recorded_candidates_only(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Acme Corporation',
            'requirement_type' => 'Office Renovation',
        ]);

        $recordedIds = [];
        foreach (range(1, 12) as $number) {
            $recordedIds[] = $this->createRecordedBrief($user, [
                'client_name' => 'Acme Corporation',
                'requirement_type' => 'Office Renovation',
            ])->id;
        }
        $this->createDraft($user, [
            'client_name' => 'Acme Corporation',
            'requirement_type' => 'Office Renovation',
        ]);

        $this->actingAs($user)->post(route('prod02.duplicate-check', $draft))->assertOk();

        $candidates = $draft->events()->where('event_type', 'DUPLICATE_CHECKED')->sole()->after_data['candidates'];
        $this->assertCount(10, $candidates);
        $this->assertSame(array_slice($recordedIds, 0, 10), array_column($candidates, 'id'));
        $this->assertSame(
            array_column($candidates, 'id'),
            collect($candidates)->sortBy('id')->pluck('id')->all()
        );
        $this->assertSame(['RECORDED'], array_values(array_unique(array_column($candidates, 'status'))));
    }

    public function test_new_decision_keeps_draft_without_primary_reference_or_official_number(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->actingAs($user)
            ->post(route('prod02.duplicate-decision', $draft), [
                'duplicate_check_event_id' => $checkEvent->id,
                'decision' => 'new',
            ])
            ->assertRedirect(route('prod02.edit', $draft));

        $draft->refresh();
        $decisionEvent = $draft->events()->where('event_type', 'DUPLICATE_DECISION')->sole();
        $this->assertSame('DRAFT', $draft->status);
        $this->assertNull($draft->primary_brief_id);
        $this->assertNull($draft->request_number);
        $this->assertSame('new', $decisionEvent->after_data['decision']);
        $this->assertSame($user->id, $decisionEvent->actor_user_id);
        $this->assertSame('MARKETING', $decisionEvent->actor_role);
    }

    public function test_continuation_links_to_recorded_primary_and_keeps_draft_without_number(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $primary = $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->actingAs($user)
            ->post(route('prod02.duplicate-decision', $draft), [
                'duplicate_check_event_id' => $checkEvent->id,
                'decision' => 'continuation',
                'primary_brief_id' => $primary->id,
                'decision_reason' => 'Klien meminta tahap lanjutan dari pekerjaan yang sama.',
            ])
            ->assertRedirect(route('prod02.edit', $draft));

        $draft->refresh();
        $decisionEvent = $draft->events()->where('event_type', 'DUPLICATE_DECISION')->sole();
        $this->assertSame('DRAFT', $draft->status);
        $this->assertSame($primary->id, $draft->primary_brief_id);
        $this->assertNull($draft->request_number);
        $this->assertSame('continuation', $decisionEvent->after_data['decision']);
        $this->assertSame('Klien meminta tahap lanjutan dari pekerjaan yang sama.', $decisionEvent->reason);

        $this->get(route('prod02.edit', $draft))
            ->assertOk()
            ->assertSee('Keputusan pemeriksaan terakhir')
            ->assertSee('Kelanjutan')
            ->assertDontSee('Submit permintaan')
            ->assertDontSee('Rekam permintaan');
    }

    public function test_saving_unchanged_duplicate_fields_does_not_invalidate_check_or_primary_link(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $primary = $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->actingAs($user)
            ->put(route('prod02.update', $draft), [
                'client_name' => 'Contoso',
                'requirement_type' => 'Consultation',
            ])
            ->assertRedirect(route('prod02.edit', $draft));

        $this->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $checkEvent->id,
            'decision' => 'continuation',
            'primary_brief_id' => $primary->id,
            'decision_reason' => 'Perluasan pekerjaan yang sama.',
        ])->assertRedirect(route('prod02.edit', $draft));

        $draft->refresh();
        $this->assertSame($primary->id, $draft->primary_brief_id);
        $this->assertSame(1, $draft->events()->where('event_type', 'DUPLICATE_DECISION')->count());
    }

    public function test_duplicate_decision_requires_reason_and_marks_draft_duplicate(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $primary = $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->actingAs($user)
            ->from(route('prod02.edit', $draft))
            ->post(route('prod02.duplicate-decision', $draft), [
                'duplicate_check_event_id' => $checkEvent->id,
                'decision' => 'duplicate',
                'primary_brief_id' => $primary->id,
            ])
            ->assertRedirect(route('prod02.edit', $draft))
            ->assertSessionHasErrors('decision_reason');

        $this->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $checkEvent->id,
            'decision' => 'duplicate',
            'primary_brief_id' => $primary->id,
            'decision_reason' => 'Catatan yang sama untuk permintaan yang sama.',
        ])->assertRedirect(route('prod02.edit', $draft));

        $draft->refresh();
        $decisionEvent = $draft->events()->where('event_type', 'DUPLICATE_DECISION')->sole();
        $this->assertSame('DUPLICATE', $draft->status);
        $this->assertSame($primary->id, $draft->primary_brief_id);
        $this->assertSame('Catatan yang sama untuk permintaan yang sama.', $draft->duplicate_reason);
        $this->assertNull($draft->request_number);
        $this->assertSame('duplicate', $decisionEvent->after_data['decision']);
        $this->assertSame('DUPLICATE_DECISION', $decisionEvent->event_type);
        $this->assertSame($user->id, $decisionEvent->actor_user_id);
        $this->assertSame('MARKETING', $decisionEvent->actor_role);
    }

    public function test_decision_rechecks_when_draft_changes_after_duplicate_check(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $primary = $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $oldCheck = $this->checkDraftForDuplicates($user, $draft);

        $this->actingAs($user)
            ->put(route('prod02.update', $draft), [
                'client_name' => 'Different client',
                'requirement_type' => 'Consultation',
            ])
            ->assertRedirect(route('prod02.edit', $draft));

        $response = $this->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $oldCheck->id,
            'decision' => 'continuation',
            'primary_brief_id' => $primary->id,
            'decision_reason' => 'Continuation.',
        ]);

        $response->assertOk()->assertSee('Hasil pemeriksaan diperbarui');
        $draft->refresh();
        $this->assertSame('DRAFT', $draft->status);
        $this->assertNull($draft->primary_brief_id);
        $this->assertSame(2, $draft->events()->where('event_type', 'DUPLICATE_CHECKED')->count());
        $this->assertSame(0, $draft->events()->where('event_type', 'DUPLICATE_DECISION')->count());
    }

    public function test_decision_rejects_non_candidate_or_non_recorded_primary(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $primary = $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $notCandidate = $this->createRecordedBrief($user, [
            'client_name' => 'Unrelated',
            'requirement_type' => 'Unrelated',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->actingAs($user)
            ->from(route('prod02.edit', $draft))
            ->post(route('prod02.duplicate-decision', $draft), [
                'duplicate_check_event_id' => $checkEvent->id,
                'decision' => 'continuation',
                'primary_brief_id' => $notCandidate->id,
                'decision_reason' => 'Not a current candidate.',
            ])
            ->assertRedirect(route('prod02.edit', $draft))
            ->assertSessionHasErrors('primary_brief_id');

        $primary->status = 'CANCELLED';
        $primary->save();

        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $checkEvent->id,
            'decision' => 'continuation',
            'primary_brief_id' => $primary->id,
            'decision_reason' => 'No longer eligible.',
        ])->assertRedirect(route('prod02.edit', $draft))
            ->assertSessionHasErrors('primary_brief_id');

        $draft->refresh();
        $this->assertNull($draft->primary_brief_id);
        $this->assertSame('DRAFT', $draft->status);
    }

    public function test_decision_rejects_primary_reference_that_would_create_cycle(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $primary = $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $primary->primary_brief_id = $draft->id;
        $primary->save();
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->actingAs($user)
            ->from(route('prod02.edit', $draft))
            ->post(route('prod02.duplicate-decision', $draft), [
                'duplicate_check_event_id' => $checkEvent->id,
                'decision' => 'continuation',
                'primary_brief_id' => $primary->id,
                'decision_reason' => 'Cyclic references are rejected.',
            ])
            ->assertRedirect(route('prod02.edit', $draft))
            ->assertSessionHasErrors('primary_brief_id');
    }

    public function test_duplicate_status_is_read_only_in_marketing_draft_list_and_shows_primary(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $primary = $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $checkEvent->id,
            'decision' => 'duplicate',
            'primary_brief_id' => $primary->id,
            'decision_reason' => 'Data mengulang permintaan yang sama.',
        ])->assertRedirect(route('prod02.edit', $draft));

        $this->actingAs($user)
            ->get(route('prod02.index'))
            ->assertOk()
            ->assertSee('DUPLICATE')
            ->assertSee($primary->request_number)
            ->assertSee('Data mengulang permintaan yang sama.')
            ->assertDontSee(route('prod02.edit', $draft));

        $this->get(route('prod02.edit', $draft))->assertForbidden();
    }

    public function test_non_marketing_cannot_check_or_decide_duplicate_candidates(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherUser = $this->createUser('other@example.test');
        $this->grantMarketingRole($owner);
        $draft = $this->createDraft($owner, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);

        $this->actingAs($otherUser)
            ->post(route('prod02.duplicate-check', $draft))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('prod02.duplicate-check', $draft))
            ->assertOk();
        $checkEvent = $draft->events()->where('event_type', 'DUPLICATE_CHECKED')->sole();

        $this->actingAs($otherUser)
            ->post(route('prod02.duplicate-decision', $draft), [
                'duplicate_check_event_id' => $checkEvent->id,
                'decision' => 'new',
            ])
            ->assertForbidden();

        $this->assertSame(0, $draft->events()->where('event_type', 'DUPLICATE_DECISION')->count());
    }

    public function test_marketing_can_submit_valid_new_request_and_records_number_and_audit_atomically(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'communication_source' => 'Email',
            'requirement_type' => 'Consultation',
            'description' => 'Initial consultation request',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);

        $this->actingAs($user)
            ->post(route('prod02.duplicate-decision', $draft), [
                'duplicate_check_event_id' => $checkEvent->id,
                'decision' => 'new',
            ])
            ->assertRedirect(route('prod02.edit', $draft));

        $this->get(route('prod02.edit', ['brief' => $draft->id, 'step' => 5]))
            ->assertOk()
            ->assertSee('Ajukan Permintaan');

        $this->post(route('prod02.submit', $draft))
            ->assertRedirect(route('prod02.show', $draft))
            ->assertSessionHas('message', 'Permintaan berhasil direkam.');

        $draft->refresh();
        $event = $draft->events()->where('event_type', 'REQUEST_RECORDED')->sole();
        $year = now()->format('Y');

        $this->assertSame('RECORDED', $draft->status);
        $this->assertSame('REQ-'.$year.'-0001', $draft->request_number);
        $this->assertNotNull($draft->recorded_at);
        $this->assertSame($user->id, $event->actor_user_id);
        $this->assertSame('MARKETING', $event->actor_role);
        $this->assertSame('DRAFT', $event->before_data['status']);
        $this->assertSame('RECORDED', $event->after_data['status']);
        $this->assertSame($draft->request_number, $event->after_data['request_number']);
        $this->assertSame('Email', $event->after_data['communication_source']);
        $this->assertSame($checkEvent->id, $event->after_data['duplicate_check_event_id']);
        $this->assertNotNull($event->occurred_at);

        $this->get(route('prod02.show', $draft))
            ->assertOk()
            ->assertSee($draft->request_number)
            ->assertSee('RECORDED');
        $this->get(route('prod02.edit', $draft))->assertForbidden();
        $this->assertDatabaseHas('request_number_sequences', [
            'year' => (int) $year,
            'last_number' => 1,
        ]);
    }

    public function test_submission_revalidates_each_required_field_and_communication_source(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $this->actingAs($user);

        foreach ([
            'client_name',
            'communication_source',
            'requirement_type',
            'description',
        ] as $field) {
            $draft = $this->createDraft($user, [
                'client_name' => 'Contoso',
                'communication_source' => 'Email',
                'requirement_type' => 'Consultation',
                'description' => 'Initial consultation request',
                $field => '   ',
            ]);

            $this->from(route('prod02.edit', $draft))
                ->post(route('prod02.submit', $draft))
                ->assertRedirect(route('prod02.edit', $draft))
                ->assertSessionHasErrors($field);

            $this->assertSame('DRAFT', $draft->fresh()->status);
            $this->assertNull($draft->fresh()->request_number);
        }

        $invalidSource = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'communication_source' => 'Invalid source',
            'requirement_type' => 'Consultation',
            'description' => 'Initial consultation request',
        ]);

        $this->from(route('prod02.edit', $invalidSource))
            ->post(route('prod02.submit', $invalidSource))
            ->assertRedirect(route('prod02.edit', $invalidSource))
            ->assertSessionHasErrors('communication_source');

        $this->assertDatabaseCount('request_number_sequences', 0);
        $this->assertDatabaseCount('client_brief_events', 5);
    }

    public function test_repeated_submit_cannot_issue_a_second_number_or_recorded_event(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'communication_source' => 'Email',
            'requirement_type' => 'Consultation',
            'description' => 'Initial consultation request',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);
        $this->actingAs($user)->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $checkEvent->id,
            'decision' => 'new',
        ])->assertRedirect(route('prod02.edit', $draft));

        $this->post(route('prod02.submit', $draft))
            ->assertRedirect(route('prod02.show', $draft));
        $firstRequestNumber = $draft->fresh()->request_number;

        $this->post(route('prod02.submit', $draft))->assertForbidden();

        $draft->refresh();
        $this->assertSame('RECORDED', $draft->status);
        $this->assertSame($firstRequestNumber, $draft->request_number);
        $this->assertSame(1, $draft->events()->where('event_type', 'REQUEST_RECORDED')->count());
        $this->assertDatabaseHas('request_number_sequences', [
            'year' => (int) now()->format('Y'),
            'last_number' => 1,
        ]);
    }

    public function test_submit_rejects_non_marketing_non_owner_and_non_new_decisions(): void
    {
        $owner = $this->createUser('submit-owner@example.test');
        $otherMarketing = $this->createUser('submit-other@example.test');
        $otherUser = $this->createUser('submit-non-marketing@example.test');
        $this->grantMarketingRole($owner);
        $this->grantMarketingRole($otherMarketing);
        $draft = $this->createDraft($owner, [
            'client_name' => 'Contoso',
            'communication_source' => 'Email',
            'requirement_type' => 'Consultation',
            'description' => 'Initial consultation request',
        ]);
        $primary = $this->createRecordedBrief($owner, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($owner, $draft);

        $this->actingAs($otherUser)
            ->post(route('prod02.submit', $draft))
            ->assertForbidden();
        $this->actingAs($otherMarketing)
            ->post(route('prod02.submit', $draft))
            ->assertForbidden();
        $this->assertSame('DRAFT', $draft->fresh()->status);
        $this->assertDatabaseCount('request_number_sequences', 0);

        $this->actingAs($owner)
            ->post(route('prod02.duplicate-decision', $draft), [
                'duplicate_check_event_id' => $checkEvent->id,
                'decision' => 'continuation',
                'primary_brief_id' => $primary->id,
                'decision_reason' => 'Kelanjutan pekerjaan.',
            ])
            ->assertRedirect(route('prod02.edit', $draft));

        $this->from(route('prod02.edit', $draft))
            ->post(route('prod02.submit', $draft))
            ->assertRedirect(route('prod02.edit', $draft))
            ->assertSessionHasErrors('submission');

        $draft->refresh();
        $this->assertSame('DRAFT', $draft->status);
        $this->assertNull($draft->request_number);
        $this->assertDatabaseCount('request_number_sequences', 0);
        $this->assertDatabaseCount('client_brief_events', 3);
    }

    public function test_submit_rechecks_when_draft_changes_after_new_decision(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'communication_source' => 'Email',
            'requirement_type' => 'Consultation',
            'description' => 'Original request',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);
        $this->actingAs($user)->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $checkEvent->id,
            'decision' => 'new',
        ])->assertRedirect(route('prod02.edit', $draft));
        $this->put(route('prod02.update', $draft), [
            'client_name' => 'Contoso',
            'communication_source' => 'Email',
            'requirement_type' => 'Consultation',
            'description' => 'Changed request',
        ])->assertRedirect(route('prod02.edit', $draft));

        $this->post(route('prod02.submit', $draft))
            ->assertOk()
            ->assertSee('Draft atau kandidat berubah')
            ->assertSee('Periksa ulang kandidat');

        $this->assertSame(2, $draft->events()->where('event_type', 'DUPLICATE_CHECKED')->count());
        $this->assertSame(0, $draft->events()->where('event_type', 'REQUEST_RECORDED')->count());
        $this->assertSame('DRAFT', $draft->fresh()->status);
        $this->assertDatabaseCount('request_number_sequences', 0);
        $this->get(route('prod02.edit', $draft))
            ->assertOk()
            ->assertDontSee('Submit permintaan baru');
    }

    public function test_submit_rechecks_when_recorded_candidate_set_changes_after_decision(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'communication_source' => 'Email',
            'requirement_type' => 'Consultation',
            'description' => 'Initial consultation request',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);
        $this->actingAs($user)->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $checkEvent->id,
            'decision' => 'new',
        ])->assertRedirect(route('prod02.edit', $draft));
        $this->createRecordedBrief($user, [
            'client_name' => 'Contoso',
            'requirement_type' => 'Consultation',
            'description' => 'Initial consultation request',
        ]);

        $this->post(route('prod02.submit', $draft))
            ->assertOk()
            ->assertSee('Draft atau kandidat berubah')
            ->assertSee('Kandidat RECORDED')
            ->assertSee('REQ-2026-');

        $this->assertSame(2, $draft->events()->where('event_type', 'DUPLICATE_CHECKED')->count());
        $this->assertSame('DRAFT', $draft->fresh()->status);
        $this->assertNull($draft->fresh()->request_number);
        $this->assertDatabaseCount('request_number_sequences', 0);
    }

    public function test_failed_request_recorded_audit_rolls_back_number_status_and_event(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $draft = $this->createDraft($user, [
            'client_name' => 'Contoso',
            'communication_source' => 'Email',
            'requirement_type' => 'Consultation',
            'description' => 'Initial consultation request',
        ]);
        $checkEvent = $this->checkDraftForDuplicates($user, $draft);
        $this->actingAs($user)->post(route('prod02.duplicate-decision', $draft), [
            'duplicate_check_event_id' => $checkEvent->id,
            'decision' => 'new',
        ])->assertRedirect(route('prod02.edit', $draft));

        ClientBriefEvent::creating(function (ClientBriefEvent $event): void {
            if ($event->event_type === 'REQUEST_RECORDED') {
                throw new \RuntimeException('Simulated audit insert failure.');
            }
        });

        $this->withoutExceptionHandling();
        try {
            $this->post(route('prod02.submit', $draft));
            $this->fail('The simulated audit failure should abort submission.');
        } catch (\Throwable $exception) {
            $this->assertStringContainsString('Simulated audit insert failure.', $exception->getMessage());
        }

        $draft->refresh();
        $this->assertSame('DRAFT', $draft->status);
        $this->assertNull($draft->request_number);
        $this->assertNull($draft->recorded_at);
        $this->assertSame(0, $draft->events()->where('event_type', 'REQUEST_RECORDED')->count());
        $this->assertDatabaseCount('request_number_sequences', 0);
    }

    public function test_request_number_allocator_waits_for_overlapping_commit_on_separate_postgresql_transactions(): void
    {
        $result = $this->runOverlappingSequenceAllocation(false);

        $this->assertTrue($result['second_was_blocked_by_first']);
        $this->assertSame(1, $result['first_number']);
        $this->assertSame(2, $result['second_number']);
        $this->assertSame(2, $result['final_counter']);
    }

    public function test_request_number_allocator_recovers_cleanly_when_first_overlapping_transaction_rolls_back(): void
    {
        $result = $this->runOverlappingSequenceAllocation(true);

        $this->assertTrue($result['second_was_blocked_by_first']);
        $this->assertSame(1, $result['first_number']);
        $this->assertSame(1, $result['second_number']);
        $this->assertSame(1, $result['final_counter']);
    }

    /**
     * @return array{second_was_blocked_by_first: bool, first_number: int, second_number: int, final_counter: int}
     */
    private function runOverlappingSequenceAllocation(bool $rollbackFirst): array
    {
        if (! function_exists('pg_connect') || ! function_exists('pg_send_query')) {
            $this->markTestSkipped('PostgreSQL async connections and pg_blocking_pids() are required for this concurrency test.');
        }

        $year = random_int(30000, 32767);
        $this->assertDatabaseMissing('request_number_sequences', ['year' => $year]);

        $config = config('database.connections.pgsql');
        $dsn = collect([
            'host' => (string) $config['host'],
            'port' => (string) $config['port'],
            'dbname' => (string) $config['database'],
            'user' => (string) $config['username'],
            'password' => (string) $config['password'],
        ])->map(function (string $value, string $key): string {
            $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);

            return "{$key}='{$escaped}'";
        })->implode(' ');

        $firstConnection = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        $secondConnection = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        $observerConnection = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        $firstTransactionOpen = false;
        $secondTransactionOpen = false;
        $secondQueryPending = false;
        $safeToCleanup = false;

        try {
            $this->assertNotFalse($firstConnection);
            $this->assertNotFalse($secondConnection);
            $this->assertNotFalse($observerConnection);

            foreach ([$firstConnection, $secondConnection, $observerConnection] as $connection) {
                $identity = pg_query(
                    $connection,
                    'SELECT current_database() AS database, current_user AS role, session_user AS session_role'
                );
                $this->assertNotFalse($identity);
                $identityRow = pg_fetch_assoc($identity);
                $this->assertSame('enterprise_bpm_test', $identityRow['database']);
                $this->assertSame('enterprise_bpm_test_user', $identityRow['role']);
                $this->assertSame('enterprise_bpm_test_user', $identityRow['session_role']);
            }
            $safeToCleanup = true;

            $firstPid = $this->postgresBackendPid($firstConnection);
            $secondPid = $this->postgresBackendPid($secondConnection);
            $this->assertNotSame($firstPid, $secondPid);
            $this->assertTrue(pg_query($firstConnection, 'BEGIN') !== false);
            $firstTransactionOpen = true;

            $sql = "INSERT INTO request_number_sequences (year, last_number, created_at, updated_at)
                    VALUES ({$year}, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ON CONFLICT (year) DO UPDATE
                    SET last_number = request_number_sequences.last_number + 1,
                        updated_at = CURRENT_TIMESTAMP
                    RETURNING last_number";
            $firstResult = pg_query($firstConnection, $sql);
            $this->assertNotFalse($firstResult);
            $firstNumber = (int) pg_fetch_result($firstResult, 0, 'last_number');
            $this->assertSame(1, $firstNumber);

            $this->assertTrue(pg_query($secondConnection, 'BEGIN') !== false);
            $secondTransactionOpen = true;
            $this->assertTrue(pg_send_query($secondConnection, $sql));
            $secondQueryPending = true;
            $this->waitUntilPostgresBackendIsBlocked($observerConnection, $secondPid, $firstPid);
            $this->assertTrue(pg_connection_busy($secondConnection));

            $firstRelease = pg_query($firstConnection, $rollbackFirst ? 'ROLLBACK' : 'COMMIT');
            $this->assertNotFalse($firstRelease);
            $firstTransactionOpen = false;

            $secondResult = pg_get_result($secondConnection);
            $this->assertNotFalse($secondResult);
            $secondQueryPending = false;
            $this->assertSame(PGSQL_TUPLES_OK, pg_result_status($secondResult));
            $secondNumber = (int) pg_fetch_result($secondResult, 0, 'last_number');

            $this->assertNotFalse(pg_query($secondConnection, 'COMMIT'));
            $secondTransactionOpen = false;

            $counterResult = pg_query(
                $observerConnection,
                "SELECT last_number FROM request_number_sequences WHERE year = {$year}"
            );
            $this->assertNotFalse($counterResult);
            $finalCounter = (int) pg_fetch_result($counterResult, 0, 'last_number');

            return [
                'second_was_blocked_by_first' => true,
                'first_number' => $firstNumber,
                'second_number' => $secondNumber,
                'final_counter' => $finalCounter,
            ];
        } finally {
            if ($secondConnection instanceof Connection && $secondQueryPending) {
                if (pg_connection_busy($secondConnection)) {
                    pg_cancel_query($secondConnection);
                }
                while (pg_get_result($secondConnection) !== false) {
                }
            }

            if ($secondConnection instanceof Connection && $secondTransactionOpen) {
                pg_query($secondConnection, 'ROLLBACK');
            }
            if ($firstConnection instanceof Connection && $firstTransactionOpen) {
                pg_query($firstConnection, 'ROLLBACK');
            }
            if ($safeToCleanup && $observerConnection instanceof Connection) {
                $cleanup = pg_query(
                    $observerConnection,
                    "DELETE FROM request_number_sequences WHERE year = {$year}"
                );
                if ($cleanup === false) {
                    throw new \RuntimeException('Could not clean the isolated test sequence row.');
                }
            }

            foreach ([$firstConnection, $secondConnection, $observerConnection] as $connection) {
                if ($connection instanceof Connection) {
                    pg_close($connection);
                }
            }
        }
    }

    private function postgresBackendPid(Connection $connection): int
    {
        $result = pg_query($connection, 'SELECT pg_backend_pid()');
        $this->assertNotFalse($result);

        return (int) pg_fetch_result($result, 0, 0);
    }

    private function waitUntilPostgresBackendIsBlocked(
        Connection $observerConnection,
        int $blockedPid,
        int $blockingPid
    ): void {
        $deadline = hrtime(true) + 5_000_000_000;

        do {
            $result = pg_query(
                $observerConnection,
                "SELECT {$blockingPid} = ANY(pg_blocking_pids({$blockedPid})) AS blocked,
                        wait_event_type
                 FROM pg_stat_activity
                 WHERE pid = {$blockedPid}"
            );
            $this->assertNotFalse($result);
            $row = pg_fetch_assoc($result);

            if ($row !== false && $row['blocked'] === 't' && $row['wait_event_type'] === 'Lock') {
                return;
            }

            usleep(10_000);
        } while (hrtime(true) < $deadline);

        $this->fail('PostgreSQL did not report transaction two blocked by transaction one before timeout.');
    }

    private function createUser(string $email = 'marketing@example.test'): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('test-password'),
        ]);
    }

    private function grantMarketingRole(User $user): void
    {
        $roleId = DB::table('bpm_roles')->where('code', 'MARKETING')->value('id');
        if ($roleId === null) {
            $roleId = DB::table('bpm_roles')->insertGetId([
                'code' => 'MARKETING',
                'name' => 'Marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('bpm_role_user')->insert([
            'user_id' => $user->id,
            'bpm_role_id' => $roleId,
        ]);
    }

    private function createDraft(User $user, array $attributes): ClientBrief
    {
        return DB::transaction(function () use ($user, $attributes): ClientBrief {
            $brief = new ClientBrief($attributes);
            $brief->marketing_user_id = $user->id;
            $brief->status = 'DRAFT';
            $brief->save();

            $brief->events()->create([
                'event_type' => 'DRAFT_CREATED',
                'actor_user_id' => $user->id,
                'actor_role' => 'MARKETING',
                'before_data' => null,
                'after_data' => $brief->only(['id', 'status', 'marketing_user_id', ...array_keys($attributes)]),
                'occurred_at' => now(),
            ]);

            return $brief;
        });
    }

    private function createRecordedBrief(User $user, array $attributes): ClientBrief
    {
        $brief = new ClientBrief($attributes);
        $brief->marketing_user_id = $user->id;
        $brief->status = 'RECORDED';
        $brief->request_number = 'REQ-2026-'.str_pad((string) (ClientBrief::query()->count() + 1), 4, '0', STR_PAD_LEFT);
        $brief->recorded_at = now();
        $brief->save();

        return $brief;
    }

    private function checkDraftForDuplicates(User $user, ClientBrief $draft): ClientBriefEvent
    {
        $this->actingAs($user)->post(route('prod02.duplicate-check', $draft))->assertOk();

        return $draft->events()->where('event_type', 'DUPLICATE_CHECKED')->latest('id')->firstOrFail();
    }
}
