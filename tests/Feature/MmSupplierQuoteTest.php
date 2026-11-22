<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialPrice;
use App\Models\MmMaterialVariant;
use App\Models\MmSupplier;
use App\Models\MmSupplierQuote;
use App\Models\MmUnit;
use App\Models\User;
use App\Services\MmSupplierQuoteService;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MmSupplierQuoteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_supplier_quotes_are_pending_and_cannot_self_approve(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $firstSupplier = $this->createSupplier();
        $secondSupplier = $this->createSupplier();
        $priorQuote = $this->createQuote($catalog['variant'], $firstSupplier, $catalog['unit'], [
            'amount' => '125.0000',
            'quoted_on' => '2026-11-07',
        ]);

        $this->actingAs($procurement)->post(route('mm05.quotes.store'), [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $firstSupplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '150.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-08',
            'is_active' => false,
            'approval_status' => MmSupplierQuote::STATUS_APPROVED,
            'approved_by' => $procurement->id,
            'approved_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('mm05.quotes.index'));

        $firstNewQuote = MmSupplierQuote::query()
            ->where('supplier_id', $firstSupplier->id)
            ->where('quoted_on', '2026-11-08')
            ->sole();
        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $firstNewQuote->id,
            'material_variant_id' => $catalog['variant']->id,
            'is_active' => true,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->assertDatabaseHas('mm_supplier_quote_approval_events', [
            'quote_id' => $firstNewQuote->id,
            'from_status' => null,
            'to_status' => MmSupplierQuote::STATUS_PENDING,
            'actor_id' => $procurement->id,
        ]);

        $this->post(route('mm05.quotes.store'), [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $secondSupplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '175.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-08',
        ])->assertRedirect(route('mm05.quotes.index'));

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $priorQuote->id,
            'amount' => '125.0000',
        ]);
        $this->assertDatabaseHas('mm_supplier_quotes', [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $secondSupplier->id,
            'quoted_on' => '2026-11-08',
            'amount' => '175.0000',
            'is_active' => true,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
        ]);
        $this->assertSame(
            3,
            MmSupplierQuote::query()->where('material_variant_id', $catalog['variant']->id)->count(),
        );
    }

    public function test_procurement_records_quote_by_material_without_selecting_a_variant(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();

        $this->actingAs($procurement)
            ->get(route('mm05.quotes.index'))
            ->assertRedirect(route('mm05.prices.index'));

        $this->actingAs($procurement)->post(route('mm05.quotes.store'), [
            'material_id' => $catalog['variant']->material_id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '150.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-11',
        ])->assertRedirect(route('mm05.quotes.index'));

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
            'quoted_on' => '2026-11-11',
            'amount' => '150.0000',
        ]);
    }

    public function test_inactive_supplier_cannot_be_selected_for_a_new_quote(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier(false);

        $this->actingAs($procurement)->post(route('mm05.quotes.store'), [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '150.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-09',
        ])->assertSessionHasErrors('supplier_id');

        $this->assertDatabaseMissing('mm_supplier_quotes', [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_new_supplier_quote_rejects_a_base_only_unit_as_price_unit(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $baseOnlyUnit = MmUnit::query()->create([
            'code' => 'UOM-BASE-'.$catalog['variant']->id,
            'name' => 'Base-only quote unit',
            'usage_group' => 'base',
        ]);

        $this->actingAs($procurement)->post(route('mm05.quotes.store'), [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $baseOnlyUnit->id,
            'amount' => '150.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-09',
        ])->assertSessionHasErrors('price_unit_id');

        $this->assertDatabaseMissing('mm_supplier_quotes', [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_admin_bpm_can_approve_a_quote_and_records_approver_and_audit_event(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);

        $this->actingAs($admin)->post(route('mm05.quotes.approve', $quote), [
            'approval_status' => MmSupplierQuote::STATUS_REJECTED,
            'recorded_by' => $admin->id,
            'approved_by' => 999999,
            'approved_at' => '2020-01-01 00:00:00',
        ])->assertRedirect(route('mm05.quotes.index'));

        $quote->refresh();
        $this->assertNotSame((int) $admin->id, (int) $quote->recorded_by);
        $this->assertSame(MmSupplierQuote::STATUS_APPROVED, $quote->approval_status);
        $this->assertSame($admin->id, $quote->approved_by);
        $this->assertNotNull($quote->approved_at);
        $this->assertDatabaseHas('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'from_status' => MmSupplierQuote::STATUS_PENDING,
            'to_status' => MmSupplierQuote::STATUS_APPROVED,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_procurement_can_approve_a_pending_quote(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);

        $this->actingAs($procurement)->post(route('mm05.quotes.approve', $quote))
            ->assertRedirect(route('mm05.quotes.index'));

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'approval_status' => MmSupplierQuote::STATUS_APPROVED,
            'approved_by' => $procurement->id,
        ]);
        $this->assertNotSame((int) $quote->recorded_by, (int) $procurement->id);
    }

    public function test_quote_creator_cannot_approve_own_quote_and_database_state_is_unchanged(): void
    {
        $creator = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote($catalog['variant'], $this->createSupplier(), $catalog['unit'], [
            'recorded_by' => $creator->id,
        ]);
        $spoofedCreator = $this->createUserWithRole('ADMIN_BPM');

        $this->actingAs($creator)
            ->post(route('mm05.quotes.approve', $quote), [
                'recorded_by' => $spoofedCreator->id,
                'approved_by' => $spoofedCreator->id,
                'approved_at' => '2020-01-01 00:00:00',
            ])
            ->assertSessionHasErrors('quote');

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'recorded_by' => $creator->id,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->assertSame(
            0,
            DB::table('mm_supplier_quote_approval_events')->where('quote_id', $quote->id)->count(),
        );
        $this->assertDatabaseMissing('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'to_status' => MmSupplierQuote::STATUS_APPROVED,
        ]);
    }

    public function test_quote_without_recorded_by_cannot_be_approved_and_database_state_is_unchanged(): void
    {
        $approver = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote($catalog['variant'], $this->createSupplier(), $catalog['unit'], [
            'recorded_by' => null,
        ]);

        $this->actingAs($approver)
            ->post(route('mm05.quotes.approve', $quote))
            ->assertSessionHasErrors('quote');

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'recorded_by' => null,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->assertSame(
            0,
            DB::table('mm_supplier_quote_approval_events')->where('quote_id', $quote->id)->count(),
        );
        $this->assertDatabaseMissing('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'to_status' => MmSupplierQuote::STATUS_APPROVED,
        ]);
    }

    public function test_inactive_quote_cannot_be_approved_and_database_state_is_unchanged(): void
    {
        $approver = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote($catalog['variant'], $this->createSupplier(), $catalog['unit']);
        DB::table('mm_supplier_quotes')
            ->where('id', $quote->id)
            ->update(['is_active' => false]);

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'is_active' => false,
        ]);

        $this->actingAs($approver)
            ->post(route('mm05.quotes.approve', $quote))
            ->assertSessionHasErrors('quote');

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'is_active' => false,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->assertSame(
            0,
            DB::table('mm_supplier_quote_approval_events')->where('quote_id', $quote->id)->count(),
        );
        $this->assertDatabaseMissing('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'to_status' => MmSupplierQuote::STATUS_APPROVED,
        ]);
    }

    public function test_non_pending_quote_cannot_be_approved_again(): void
    {
        $firstApprover = $this->createUserWithRole('ADMIN_BPM');
        $secondApprover = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote($catalog['variant'], $this->createSupplier(), $catalog['unit']);

        app(MmSupplierQuoteService::class)->approveQuote($quote, $firstApprover);
        $approvedAt = DB::table('mm_supplier_quotes')->where('id', $quote->id)->value('approved_at');
        $this->actingAs($secondApprover)
            ->post(route('mm05.quotes.approve', $quote))
            ->assertSessionHasErrors('quote');

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'approval_status' => MmSupplierQuote::STATUS_APPROVED,
            'approved_by' => $firstApprover->id,
        ]);
        $this->assertSame(
            $approvedAt,
            DB::table('mm_supplier_quotes')->where('id', $quote->id)->value('approved_at'),
        );
        $this->assertSame(
            1,
            DB::table('mm_supplier_quote_approval_events')->where('quote_id', $quote->id)->count(),
        );
        $this->assertDatabaseMissing('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'actor_id' => $secondApprover->id,
        ]);
    }

    public function test_quote_creator_can_reject_own_quote_without_changing_rejection_behavior(): void
    {
        $creator = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote($catalog['variant'], $this->createSupplier(), $catalog['unit'], [
            'recorded_by' => $creator->id,
        ]);

        $this->actingAs($creator)
            ->post(route('mm05.quotes.reject', $quote), ['reason' => 'Dokumen perlu diperbaiki.'])
            ->assertRedirect(route('mm05.quotes.index'));

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'recorded_by' => $creator->id,
            'approval_status' => MmSupplierQuote::STATUS_REJECTED,
            'rejected_by' => $creator->id,
            'rejection_reason' => 'Dokumen perlu diperbaiki.',
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->assertDatabaseHas('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'event_type' => MmSupplierQuote::STATUS_REJECTED,
            'actor_id' => $creator->id,
            'reason' => 'Dokumen perlu diperbaiki.',
        ]);
    }

    public function test_procurement_can_reject_a_pending_quote_through_the_endpoint(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote($catalog['variant'], $this->createSupplier(), $catalog['unit']);

        $this->actingAs($procurement)
            ->post(route('mm05.quotes.reject', $quote), ['reason' => 'Dokumen perlu diperbaiki.'])
            ->assertRedirect(route('mm05.quotes.index'));

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'approval_status' => MmSupplierQuote::STATUS_REJECTED,
            'rejected_by' => $procurement->id,
            'rejection_reason' => 'Dokumen perlu diperbaiki.',
        ]);
        $this->assertDatabaseHas('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'event_type' => MmSupplierQuote::STATUS_REJECTED,
            'actor_id' => $procurement->id,
            'reason' => 'Dokumen perlu diperbaiki.',
        ]);
    }

    public function test_approval_is_forbidden_to_roles_without_quote_approval_authority(): void
    {
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
        $readOnlyUser = $this->createUserWithRole('ADMIN_PRODUKSI');

        $this->actingAs($readOnlyUser)
            ->post(route('mm05.quotes.approve', $quote))
            ->assertForbidden();

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $this->assertSame(
            0,
            DB::table('mm_supplier_quote_approval_events')->where('quote_id', $quote->id)->count(),
        );
        $this->assertDatabaseMissing('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'to_status' => MmSupplierQuote::STATUS_APPROVED,
        ]);
    }

    public function test_rejection_records_actor_reason_and_audit_event(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);

        $this->actingAs($admin)->post(route('mm05.quotes.reject', $quote), [
            'reason' => 'Dokumen penawaran belum lengkap.',
        ])->assertRedirect(route('mm05.quotes.index'));

        $quote->refresh();
        $this->assertSame(MmSupplierQuote::STATUS_REJECTED, $quote->approval_status);
        $this->assertSame($admin->id, $quote->rejected_by);
        $this->assertSame('Dokumen penawaran belum lengkap.', $quote->rejection_reason);
        $this->assertNotNull($quote->rejected_at);
        $this->assertDatabaseHas('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'from_status' => MmSupplierQuote::STATUS_PENDING,
            'to_status' => MmSupplierQuote::STATUS_REJECTED,
            'actor_id' => $admin->id,
            'reason' => 'Dokumen penawaran belum lengkap.',
        ]);
    }

    public function test_rejection_endpoint_rejects_missing_empty_and_whitespace_reasons(): void
    {
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit'],
        );

        foreach ([[], ['reason' => ''], ['reason' => '   ']] as $payload) {
            $this->actingAs($admin)
                ->post(route('mm05.quotes.reject', $quote), $payload)
                ->assertSessionHasErrors('reason');
        }

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
            'rejection_reason' => null,
        ]);
    }

    public function test_database_rejection_constraint_rejects_invalid_reason_states(): void
    {
        $actor = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit'],
        );
        $rejectedMetadata = [
            'approval_status' => MmSupplierQuote::STATUS_REJECTED,
            'rejected_by' => $actor->id,
            'rejected_at' => now(),
        ];

        foreach ([
            [[...$rejectedMetadata, 'rejection_reason' => null], 'mm_supplier_quotes_approval_metadata_check'],
            [[...$rejectedMetadata, 'rejection_reason' => ''], 'mm_supplier_quotes_approval_metadata_check'],
            [[...$rejectedMetadata, 'rejection_reason' => " \t "], 'mm_supplier_quotes_rejection_reason_whitespace_check'],
            [
                ['approval_status' => MmSupplierQuote::STATUS_PENDING, 'rejection_reason' => 'not allowed'],
                'mm_supplier_quotes_approval_metadata_check',
            ],
            [
                [
                    'approval_status' => MmSupplierQuote::STATUS_APPROVED,
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                    'rejection_reason' => 'not allowed',
                ],
                'mm_supplier_quotes_approval_metadata_check',
            ],
        ] as [$invalidState, $constraint]) {
            $this->assertDatabaseConstraintViolation(
                fn () => DB::table('mm_supplier_quotes')->where('id', $quote->id)->update($invalidState),
                $constraint,
            );
        }

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
            'rejection_reason' => null,
        ]);
    }

    public function test_approval_actor_foreign_keys_reject_unknown_users_and_restrict_user_deletion(): void
    {
        $approver = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $approvedQuote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
        app(MmSupplierQuoteService::class)->approveQuote($approvedQuote, $approver);

        $this->assertDatabaseConstraintViolation(
            fn () => DB::table('mm_supplier_quotes')
                ->where('id', $approvedQuote->id)
                ->update(['approved_by' => 999999999]),
            'mm_quote_approved_by_users_fk',
        );

        $rejectedQuote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
        app(MmSupplierQuoteService::class)->rejectQuote($rejectedQuote, $approver, 'Rejected for testing.');
        $this->assertDatabaseConstraintViolation(
            fn () => DB::table('mm_supplier_quotes')
                ->where('id', $rejectedQuote->id)
                ->update(['rejected_by' => 999999999]),
            'mm_quote_rejected_by_users_fk',
        );

        $this->assertDatabaseConstraintViolation(
            fn () => DB::table('mm_supplier_quote_approval_events')->insert([
                'quote_id' => $approvedQuote->id,
                'event_type' => 'APPROVED',
                'from_status' => MmSupplierQuote::STATUS_PENDING,
                'to_status' => MmSupplierQuote::STATUS_APPROVED,
                'actor_id' => 999999999,
                'acted_at' => now(),
            ]),
            'mm_quote_approval_actor_users_fk',
        );

        DB::table('mm_supplier_quote_approval_events')
            ->where('quote_id', $approvedQuote->id)
            ->update(['actor_id' => null]);
        DB::table('bpm_role_user')->where('user_id', $approver->id)->delete();
        $this->assertDatabaseConstraintViolation(
            fn () => DB::table('users')->where('id', $approver->id)->delete(),
            'mm_quote_approved_by_users_fk',
        );

        $rejector = $this->createUserWithRole('PROCUREMENT');
        $isolatedRejectedQuote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
        app(MmSupplierQuoteService::class)->rejectQuote($isolatedRejectedQuote, $rejector, 'Rejected for testing.');
        DB::table('mm_supplier_quote_approval_events')
            ->where('quote_id', $isolatedRejectedQuote->id)
            ->update(['actor_id' => null]);
        DB::table('bpm_role_user')->where('user_id', $rejector->id)->delete();
        $this->assertDatabaseConstraintViolation(
            fn () => DB::table('users')->where('id', $rejector->id)->delete(),
            'mm_quote_rejected_by_users_fk',
        );

        $eventActor = $this->createUserWithRole('ADMIN_BPM');
        $eventQuote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
        DB::table('mm_supplier_quote_approval_events')->insert([
            'quote_id' => $eventQuote->id,
            'event_type' => 'PENDING',
            'from_status' => null,
            'to_status' => MmSupplierQuote::STATUS_PENDING,
            'actor_id' => $eventActor->id,
            'acted_at' => now(),
        ]);
        DB::table('bpm_role_user')->where('user_id', $eventActor->id)->delete();
        $this->assertDatabaseConstraintViolation(
            fn () => DB::table('users')->where('id', $eventActor->id)->delete(),
            'mm_quote_approval_actor_users_fk',
        );
    }

    public function test_quote_activation_and_deactivation_are_audited_and_no_ops_are_ignored(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit'],
        );

        $this->actingAs($procurement)->post(route('mm05.quotes.deactivate', $quote), [
            'reason' => 'Supplier account review.',
        ])->assertRedirect(route('mm05.quotes.index'));

        $deactivatedEvent = DB::table('mm_supplier_quote_approval_events')
            ->where('quote_id', $quote->id)
            ->where('event_type', 'DEACTIVATED')
            ->first();
        $this->assertNotNull($deactivatedEvent);
        $this->assertSame($procurement->id, (int) $deactivatedEvent->actor_id);
        $this->assertSame(true, $deactivatedEvent->previous_is_active);
        $this->assertSame(false, $deactivatedEvent->new_is_active);
        $this->assertSame('Supplier account review.', $deactivatedEvent->reason);
        $this->assertNotNull($deactivatedEvent->acted_at);

        $this->post(route('mm05.quotes.deactivate', $quote))
            ->assertRedirect(route('mm05.quotes.index'));
        $this->assertDatabaseCount('mm_supplier_quote_approval_events', 1);

        $this->post(route('mm05.quotes.activate', $quote), [
            'reason' => 'Review completed.',
        ])->assertRedirect(route('mm05.quotes.index'));

        $activatedEvent = DB::table('mm_supplier_quote_approval_events')
            ->where('quote_id', $quote->id)
            ->where('event_type', 'ACTIVATED')
            ->first();
        $this->assertNotNull($activatedEvent);
        $this->assertSame($procurement->id, (int) $activatedEvent->actor_id);
        $this->assertSame(false, $activatedEvent->previous_is_active);
        $this->assertSame(true, $activatedEvent->new_is_active);
        $this->assertSame('Review completed.', $activatedEvent->reason);
        $this->assertNotNull($activatedEvent->acted_at);

        $this->post(route('mm05.quotes.activate', $quote))
            ->assertRedirect(route('mm05.quotes.index'));
        $this->assertDatabaseCount('mm_supplier_quote_approval_events', 2);
    }

    public function test_activation_change_and_audit_event_roll_back_atomically(): void
    {
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit'],
        );

        try {
            DB::transaction(function () use ($quote, $procurement): void {
                app(MmSupplierQuoteService::class)->setQuoteActive(
                    $quote,
                    false,
                    $procurement,
                    'This transaction will be rolled back.',
                );
                throw new RuntimeException('Force outer transaction rollback.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Force outer transaction rollback.', $exception->getMessage());
        }

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'event_type' => 'DEACTIVATED',
        ]);
    }

    public function test_activation_service_rejects_roles_without_manage_authority(): void
    {
        $actor = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit'],
        );

        try {
            app(MmSupplierQuoteService::class)->setQuoteActive($quote, false, $actor);
            $this->fail('A role without quote management authority must not change active status.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_catalog_shows_only_approved_active_quotes_with_reference_prices(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $pendingSupplier = $this->createSupplier();
        $rejectedSupplier = $this->createSupplier();
        $inactiveQuoteSupplier = $this->createSupplier();
        $approvedSupplier = $this->createSupplier();

        $pendingQuote = $this->createQuote($catalog['variant'], $pendingSupplier, $catalog['unit']);
        $rejectedQuote = $this->createQuote($catalog['variant'], $rejectedSupplier, $catalog['unit']);
        $inactiveQuote = $this->createQuote($catalog['variant'], $inactiveQuoteSupplier, $catalog['unit']);
        $approvedQuote = $this->createQuote($catalog['variant'], $approvedSupplier, $catalog['unit']);
        app(MmSupplierQuoteService::class)->rejectQuote($rejectedQuote, $admin, 'Ditolak untuk pengujian.');
        app(MmSupplierQuoteService::class)->setQuoteActive(
            $inactiveQuote,
            false,
            $this->createUserWithRole('PROCUREMENT'),
        );
        app(MmSupplierQuoteService::class)->approveQuote($approvedQuote, $admin);

        foreach ([$pendingQuote, $rejectedQuote, $inactiveQuote, $approvedQuote] as $index => $quote) {
            $this->createPrice($catalog['variant'], $catalog['unit'], $quote, [
                'amount' => (string) (100 + $index).'.0000',
            ]);
        }

        $this->actingAs($this->createUserWithRole('ASST_MANAGER_PRODUKSI'))
            ->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee($approvedSupplier->name)
            ->assertSee('103,0000')
            ->assertDontSee($pendingSupplier->name)
            ->assertDontSee($rejectedSupplier->name)
            ->assertDontSee($inactiveQuoteSupplier->name);
    }

    public function test_quote_and_effective_date_boundaries_are_inclusive_and_null_end_dates_do_not_expire(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $boundarySupplier = $this->createSupplier();
        $unboundedSupplier = $this->createSupplier();
        $expiredQuoteSupplier = $this->createSupplier();
        $futureQuoteSupplier = $this->createSupplier();

        $boundaryQuote = $this->createQuote($catalog['variant'], $boundarySupplier, $catalog['unit'], [
            'quoted_on' => '2026-11-10',
            'valid_until' => '2026-11-10',
        ]);
        $unboundedQuote = $this->createQuote($catalog['variant'], $unboundedSupplier, $catalog['unit'], [
            'quoted_on' => '2026-11-10',
            'valid_until' => null,
        ]);
        $expiredQuote = $this->createQuote($catalog['variant'], $expiredQuoteSupplier, $catalog['unit'], [
            'valid_until' => '2026-11-09',
        ]);
        $futureQuote = $this->createQuote($catalog['variant'], $futureQuoteSupplier, $catalog['unit'], [
            'quoted_on' => '2026-11-11',
        ]);
        foreach ([$boundaryQuote, $unboundedQuote, $expiredQuote, $futureQuote] as $quote) {
            app(MmSupplierQuoteService::class)->approveQuote($quote, $admin);
        }

        $this->createPrice($catalog['variant'], $catalog['unit'], $boundaryQuote, [
            'effective_from' => '2026-11-10',
            'effective_until' => '2026-11-10',
        ]);
        $this->createPrice($catalog['variant'], $catalog['unit'], $unboundedQuote, [
            'effective_from' => '2026-11-10',
            'effective_until' => null,
        ]);
        $this->createPrice($catalog['variant'], $catalog['unit'], $expiredQuote);
        $this->createPrice($catalog['variant'], $catalog['unit'], $futureQuote);

        $this->actingAs($this->createUserWithRole('ADMIN_PRODUKSI'))
            ->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee($boundarySupplier->name)
            ->assertSee('Efektif mulai 10/11/2026')
            ->assertSee('Efektif sampai 10/11/2026')
            ->assertSee($unboundedSupplier->name)
            ->assertSee('Efektif sampai Tanpa batas akhir')
            ->assertDontSee($expiredQuoteSupplier->name)
            ->assertDontSee($futureQuoteSupplier->name);
    }

    public function test_expired_and_not_yet_effective_prices_are_excluded(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $expiredSupplier = $this->createSupplier();
        $futureSupplier = $this->createSupplier();
        $expiredQuote = $this->createQuote($catalog['variant'], $expiredSupplier, $catalog['unit']);
        $futureQuote = $this->createQuote($catalog['variant'], $futureSupplier, $catalog['unit']);
        app(MmSupplierQuoteService::class)->approveQuote($expiredQuote, $admin);
        app(MmSupplierQuoteService::class)->approveQuote($futureQuote, $admin);

        $this->createPrice($catalog['variant'], $catalog['unit'], $expiredQuote, [
            'effective_until' => '2026-11-09',
        ]);
        $this->createPrice($catalog['variant'], $catalog['unit'], $futureQuote, [
            'effective_from' => '2026-11-11',
        ]);

        $this->actingAs($this->createUserWithRole('ADMIN_PRODUKSI'))
            ->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee('Harga belum tersedia')
            ->assertDontSee($expiredSupplier->name)
            ->assertDontSee($futureSupplier->name);
    }

    public function test_catalog_chooses_latest_effective_date_separately_for_currency_and_price_unit(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $otherUnit = $this->createUnit('BOX');
        $oldSupplier = $this->createSupplier();
        $latestSupplier = $this->createSupplier();
        $otherCurrencySupplier = $this->createSupplier();
        $oldQuote = $this->createQuote($catalog['variant'], $oldSupplier, $catalog['unit']);
        $latestQuote = $this->createQuote($catalog['variant'], $latestSupplier, $catalog['unit']);
        $otherCurrencyQuote = $this->createQuote($catalog['variant'], $otherCurrencySupplier, $otherUnit, [
            'currency' => 'USD',
        ]);
        foreach ([$oldQuote, $latestQuote, $otherCurrencyQuote] as $quote) {
            app(MmSupplierQuoteService::class)->approveQuote($quote, $admin);
        }

        $this->createPrice($catalog['variant'], $catalog['unit'], $oldQuote, [
            'amount' => '100.0000',
            'effective_from' => '2026-11-08',
        ]);
        $this->createPrice($catalog['variant'], $catalog['unit'], $latestQuote, [
            'amount' => '200.0000',
            'effective_from' => '2026-11-10',
        ]);
        $this->createPrice($catalog['variant'], $otherUnit, $otherCurrencyQuote, [
            'amount' => '300.0000',
            'currency' => 'USD',
            'effective_from' => '2026-11-09',
        ]);

        $this->actingAs($this->createUserWithRole('ADMIN_PRODUKSI'))
            ->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee($latestSupplier->name)
            ->assertSee('200,0000')
            ->assertDontSee($oldSupplier->name)
            ->assertSee('USD / '.$otherUnit->code)
            ->assertSee($otherCurrencySupplier->name)
            ->assertSee('300,0000')
            ->assertSee($catalog['unit']->code);
    }

    public function test_catalog_shows_all_suppliers_tied_at_the_latest_effective_date(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $firstSupplier = $this->createSupplier();
        $secondSupplier = $this->createSupplier();
        foreach ([$firstSupplier, $secondSupplier] as $supplier) {
            $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
            app(MmSupplierQuoteService::class)->approveQuote($quote, $admin);
            $this->createPrice($catalog['variant'], $catalog['unit'], $quote, [
                'effective_from' => '2026-11-10',
            ]);
        }

        $this->actingAs($this->createUserWithRole('ADMIN_PRODUKSI'))
            ->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee($firstSupplier->name)
            ->assertSee($secondSupplier->name);
    }

    public function test_catalog_excludes_inactive_supplier_material_and_variant(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $inactiveSupplier = $this->createSupplier();
        $inactiveSupplierQuote = $this->createQuote($catalog['variant'], $inactiveSupplier, $catalog['unit']);
        app(MmSupplierQuoteService::class)->approveQuote($inactiveSupplierQuote, $admin);
        $this->createPrice($catalog['variant'], $catalog['unit'], $inactiveSupplierQuote);
        $inactiveSupplier->update(['is_active' => false]);

        $inactiveMaterialCatalog = $this->createCatalog();
        $inactiveMaterialSupplier = $this->createSupplier();
        $inactiveMaterialQuote = $this->createQuote(
            $inactiveMaterialCatalog['variant'],
            $inactiveMaterialSupplier,
            $inactiveMaterialCatalog['unit'],
        );
        app(MmSupplierQuoteService::class)->approveQuote($inactiveMaterialQuote, $admin);
        $this->createPrice(
            $inactiveMaterialCatalog['variant'],
            $inactiveMaterialCatalog['unit'],
            $inactiveMaterialQuote,
        );
        $inactiveMaterialCatalog['material']->update(['is_active' => false]);

        $inactiveVariantCatalog = $this->createCatalog();
        $inactiveVariantSupplier = $this->createSupplier();
        $inactiveVariantQuote = $this->createQuote(
            $inactiveVariantCatalog['variant'],
            $inactiveVariantSupplier,
            $inactiveVariantCatalog['unit'],
        );
        app(MmSupplierQuoteService::class)->approveQuote($inactiveVariantQuote, $admin);
        $this->createPrice(
            $inactiveVariantCatalog['variant'],
            $inactiveVariantCatalog['unit'],
            $inactiveVariantQuote,
        );
        $inactiveVariantCatalog['variant']->update(['is_active' => false]);

        $this->actingAs($this->createUserWithRole('ADMIN_PRODUKSI'))
            ->get(route('mm05.variants.index'))
            ->assertOk()
            ->assertSee($catalog['variant']->sku)
            ->assertSee('Harga belum tersedia')
            ->assertDontSee($inactiveSupplier->name)
            ->assertDontSee($inactiveMaterialSupplier->name)
            ->assertDontSee($inactiveVariantSupplier->name);
    }

    public function test_catalog_excludes_prices_without_source_quote_or_reference_type(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
        app(MmSupplierQuoteService::class)->approveQuote($quote, $admin);
        $this->createPrice($catalog['variant'], $catalog['unit'], null);
        $this->createPrice($catalog['variant'], $catalog['unit'], $quote, [
            'price_type' => 'purchase',
        ]);

        $this->actingAs($this->createUserWithRole('ADMIN_PRODUKSI'))
            ->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee('Harga belum tersedia')
            ->assertDontSee($supplier->name);
    }

    public function test_catalog_excludes_reference_prices_that_do_not_match_their_source_quote(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $admin = $this->createUserWithRole('ADMIN_BPM');
        $catalog = $this->createCatalog();
        $otherCatalog = $this->createCatalog();
        $otherUnit = $this->createUnit('BOX');
        $variantMismatchSupplier = $this->createSupplier();
        $unitMismatchSupplier = $this->createSupplier();
        $currencyMismatchSupplier = $this->createSupplier();
        $variantMismatchQuote = $this->createQuote(
            $otherCatalog['variant'],
            $variantMismatchSupplier,
            $catalog['unit'],
        );
        $unitMismatchQuote = $this->createQuote(
            $catalog['variant'],
            $unitMismatchSupplier,
            $otherUnit,
        );
        $currencyMismatchQuote = $this->createQuote(
            $catalog['variant'],
            $currencyMismatchSupplier,
            $catalog['unit'],
            ['currency' => 'USD'],
        );
        foreach ([$variantMismatchQuote, $unitMismatchQuote, $currencyMismatchQuote] as $quote) {
            app(MmSupplierQuoteService::class)->approveQuote($quote, $admin);
        }

        $this->createPrice($catalog['variant'], $catalog['unit'], $variantMismatchQuote);
        $this->createPrice($catalog['variant'], $catalog['unit'], $unitMismatchQuote);
        $this->createPrice($catalog['variant'], $catalog['unit'], $currencyMismatchQuote, [
            'currency' => 'IDR',
        ]);

        $this->actingAs($this->createUserWithRole('ADMIN_PRODUKSI'))
            ->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee('Harga belum tersedia')
            ->assertDontSee($variantMismatchSupplier->name)
            ->assertDontSee($unitMismatchSupplier->name)
            ->assertDontSee($currencyMismatchSupplier->name);
    }

    public function test_catalog_displays_no_price_message_when_no_price_is_eligible(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);

        $this->createPrice($catalog['variant'], $catalog['unit'], $quote);

        $this->actingAs($this->createUserWithRole('ADMIN_PRODUKSI'))
            ->get(route('mm05.variants.index', ['material_id' => $catalog['material']->id]))
            ->assertOk()
            ->assertSee('Harga belum tersedia')
            ->assertDontSee($supplier->name);
    }

    public function test_second_competing_approval_cannot_overwrite_the_first_decision_or_edit_approved_price(): void
    {
        $this->travelTo('2026-11-10 12:00:00');
        $firstApprover = $this->createUserWithRole('ADMIN_BPM');
        $secondApprover = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
        $staleCopyForFirstApprover = MmSupplierQuote::query()->findOrFail($quote->id);
        $staleCopyForSecondApprover = MmSupplierQuote::query()->findOrFail($quote->id);

        app(MmSupplierQuoteService::class)->approveQuote($staleCopyForFirstApprover, $firstApprover);
        $this->actingAs($secondApprover)
            ->post(route('mm05.quotes.reject', $staleCopyForSecondApprover), [
                'reason' => 'Keputusan kedua.',
            ])
            ->assertSessionHasErrors('quote');

        $this->put(route('mm05.quotes.update', $quote), [
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '900.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-08',
        ])->assertSessionHasErrors('quote');

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'approval_status' => MmSupplierQuote::STATUS_APPROVED,
            'approved_by' => $firstApprover->id,
            'rejected_by' => null,
            'amount' => '100.0000',
        ]);
        $this->assertDatabaseCount('mm_supplier_quote_approval_events', 1);
    }

    public function test_procurement_can_edit_deactivate_and_reactivate_quote_without_deleting_it(): void
    {
        $this->travelTo('2026-11-09 12:00:00');
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit'], [
            'supplier_reference' => 'REF-OLD',
        ]);
        $expiredQuote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit'], [
            'quoted_on' => '2026-11-06',
            'valid_until' => '2026-11-08',
            'supplier_reference' => 'REF-EXPIRED',
        ]);

        $this->actingAs($procurement)->put(route('mm05.quotes.update', $quote), [
            'material_variant_id' => 999999,
            'supplier_id' => 999999,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '250.0000',
            'currency' => 'idr',
            'quoted_on' => '2026-11-08',
            'valid_until' => '2026-11-20',
            'supplier_reference' => 'REF-UPDATED',
            'notes' => 'Updated quote details',
        ])->assertRedirect(route('mm05.quotes.index'));

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
            'amount' => '250.0000',
            'currency' => 'IDR',
            'supplier_reference' => 'REF-UPDATED',
        ]);
        $this->get(route('mm05.quotes.index', ['status' => 'expired']))
            ->assertRedirect(route('mm05.prices.index'));

        $this->post(route('mm05.quotes.deactivate', $quote), ['reason' => 'Supplier review.'])
            ->assertRedirect(route('mm05.quotes.index'));
        $this->assertDatabaseHas('mm_supplier_quotes', ['id' => $quote->id, 'is_active' => false]);
        $this->assertDatabaseHas('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'event_type' => 'DEACTIVATED',
            'actor_id' => $procurement->id,
            'previous_is_active' => true,
            'new_is_active' => false,
            'reason' => 'Supplier review.',
        ]);

        $this->post(route('mm05.quotes.activate', $quote), ['reason' => 'Review completed.'])
            ->assertRedirect(route('mm05.quotes.index'));
        $this->assertDatabaseHas('mm_supplier_quotes', ['id' => $quote->id, 'is_active' => true]);
        $this->assertDatabaseHas('mm_supplier_quote_approval_events', [
            'quote_id' => $quote->id,
            'event_type' => 'ACTIVATED',
            'actor_id' => $procurement->id,
            'previous_is_active' => false,
            'new_is_active' => true,
            'reason' => 'Review completed.',
        ]);
        $this->get(route('mm05.quotes.index', [
            'q' => 'REF-UPDATED',
            'material_id' => $catalog['material']->id,
            'supplier_id' => $supplier->id,
            'status' => 'active',
        ]))->assertRedirect(route('mm05.prices.index'));
    }

    public function test_read_roles_can_view_quotes_but_cannot_mutate_them(): void
    {
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);

        $this->get(route('mm05.quotes.index'))->assertRedirect(route('login'));

        foreach (['ADMIN_BPM', 'ASST_MANAGER_PRODUKSI', 'ADMIN_PRODUKSI'] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);
            $this->actingAs($user)->get(route('mm05.quotes.index'))
                ->assertRedirect(route('mm05.prices.index'));

            $this->post(route('mm05.quotes.store'), [])->assertForbidden();
            $this->put(route('mm05.quotes.update', $quote), [])->assertForbidden();
            $this->post(route('mm05.quotes.deactivate', $quote))->assertForbidden();
            $this->post(route('mm05.quotes.activate', $quote))->assertForbidden();
        }

        foreach (['ASST_MANAGER_PRODUKSI', 'ADMIN_PRODUKSI', 'MARKETING', 'DESIGNER'] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);
            $this->actingAs($user)
                ->post(route('mm05.quotes.approve', $quote))
                ->assertForbidden();
            $this->post(route('mm05.quotes.reject', $quote), [
                'reason' => 'Tidak berwenang.',
            ])->assertForbidden();
        }

        foreach (['MARKETING', 'DESIGNER'] as $roleCode) {
            $this->actingAs($this->createUserWithRole($roleCode))
                ->get(route('mm05.quotes.index'))
                ->assertForbidden();
        }

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_mutate_supplier_quotes(): void
    {
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createQuote($catalog['variant'], $supplier, $catalog['unit']);
        $quotePayload = [
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '125.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-10',
        ];

        $this->post(route('mm05.quotes.store'), [
            ...$quotePayload,
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
        ])->assertRedirect(route('login'));
        $this->put(route('mm05.quotes.update', $quote), $quotePayload)
            ->assertRedirect(route('login'));
        $this->post(route('mm05.quotes.approve', $quote))
            ->assertRedirect(route('login'));
        $this->post(route('mm05.quotes.reject', $quote), ['reason' => 'Tidak berwenang.'])
            ->assertRedirect(route('login'));
        $this->post(route('mm05.quotes.activate', $quote))
            ->assertRedirect(route('login'));
        $this->post(route('mm05.quotes.deactivate', $quote))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('mm_supplier_quotes', [
            'id' => $quote->id,
            'approval_status' => MmSupplierQuote::STATUS_PENDING,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{category: MmCategory, unit: MmUnit, material: MmMaterial, variant: MmMaterialVariant}
     */
    private function createCatalog(): array
    {
        $suffix = Str::upper(Str::random(8));
        $category = MmCategory::query()->create([
            'code' => 'CAT-Q-'.$suffix,
            'name' => 'Quote Category '.$suffix,
        ]);
        $unit = MmUnit::query()->create([
            'code' => 'UNIT-Q-'.$suffix,
            'name' => 'Quote Unit '.$suffix,
            'dimension' => 'count',
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-Q-'.$suffix,
            'name' => 'Quote Material '.$suffix,
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-Q-'.$suffix,
            'variant_name' => 'Quote Variant '.$suffix,
            'base_unit_id' => $unit->id,
        ]);

        return compact('category', 'unit', 'material', 'variant');
    }

    private function assertDatabaseConstraintViolation(Closure $operation, string $constraint): void
    {
        try {
            DB::transaction($operation);
            $this->fail("Expected database constraint [{$constraint}] to reject the operation.");
        } catch (QueryException $exception) {
            $this->assertStringContainsString($constraint, $exception->getMessage());
        }
    }

    private function createSupplier(bool $isActive = true): MmSupplier
    {
        $suffix = Str::upper(Str::random(8));

        return MmSupplier::query()->create([
            'code' => 'SUP-Q-'.$suffix,
            'name' => 'Quote Supplier '.$suffix,
            'is_active' => $isActive,
        ]);
    }

    private function createUnit(string $prefix = 'UNIT'): MmUnit
    {
        $suffix = Str::upper(Str::random(8));

        return MmUnit::query()->create([
            'code' => $prefix.'-Q-'.$suffix,
            'name' => 'Quote Unit '.$suffix,
            'dimension' => 'count',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createQuote(
        MmMaterialVariant $variant,
        MmSupplier $supplier,
        MmUnit $unit,
        array $overrides = [],
    ): MmSupplierQuote {
        $recordedBy = array_key_exists('recorded_by', $overrides)
            ? $overrides['recorded_by']
            : User::query()->create([
                'name' => 'Quote Creator '.Str::random(8),
                'email' => Str::lower(Str::random(16)).'@quote.test',
                'password' => Hash::make('test-password'),
            ])->id;

        return MmSupplierQuote::query()->create(array_merge([
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $unit->id,
            'amount' => '100.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-08',
            'recorded_by' => $recordedBy,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPrice(
        MmMaterialVariant $variant,
        MmUnit $unit,
        ?MmSupplierQuote $quote,
        array $overrides = [],
    ): MmMaterialPrice {
        return MmMaterialPrice::query()->create(array_merge([
            'material_variant_id' => $variant->id,
            'supplier_id' => $quote?->supplier_id,
            'price_unit_id' => $unit->id,
            'source_quote_id' => $quote?->id,
            'price_type' => 'reference',
            'amount' => '1000.0000',
            'currency' => $quote?->currency ?? 'IDR',
            'effective_from' => '2026-11-09',
        ], $overrides));
    }

    private function createUserWithRole(string $roleCode): User
    {
        $suffix = Str::lower(Str::random(8));
        $user = User::query()->create([
            'name' => $roleCode.' User '.$suffix,
            'email' => $suffix.'@quote.test',
            'password' => Hash::make('test-password'),
        ]);
        $role = BpmRole::query()->firstOrCreate(
            ['code' => $roleCode],
            ['name' => str_replace('_', ' ', $roleCode)],
        );
        $user->roles()->attach($role);

        return $user;
    }
}
