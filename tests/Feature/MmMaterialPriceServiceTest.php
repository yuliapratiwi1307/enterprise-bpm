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
use App\Services\MmMaterialPriceService;
use App\Services\MmReferencePriceSelector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MmMaterialPriceServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_procurement_can_record_price_from_approved_quote(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createApprovedQuote(
            $catalog['variant'],
            $supplier,
            $catalog['unit']
        );

        $this->actingAs($user)
            ->post(route('mm05.prices.store'), [
                'source_quote_id' => $quote->id,
                'effective_from' => '2026-11-09',
                'effective_until' => '2026-11-20',
                'change_reason' => 'Harga referensi terbaru.',
            ])
            ->assertRedirect(route('mm05.prices.index'))
            ->assertSessionHas('message');

        $this->assertDatabaseHas('mm_material_prices', [
            'material_variant_id' => $catalog['variant']->id,
            'price_unit_id' => $catalog['unit']->id,
            'source_quote_id' => $quote->id,
            'price_type' => 'reference',
            'amount' => '100.0000',
            'conversion_factor' => '1.00000000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-09',
            'effective_until' => '2026-11-20',
            'source_name' => $supplier->name,
            'recorded_by' => $user->id,
        ]);
    }

    public function test_browser_cannot_manipulate_price_source_or_amount(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $quote = $this->createApprovedQuote(
            $catalog['variant'],
            $supplier,
            $catalog['unit']
        );

        $otherCatalog = $this->createCatalog();

        $this->actingAs($user)
            ->post(route('mm05.prices.store'), [
                'source_quote_id' => $quote->id,
                'effective_from' => '2026-11-09',
                'effective_until' => null,
                'change_reason' => 'Test keamanan.',
                'amount' => '1.0000',
                'currency' => 'USD',
                'material_variant_id' => $otherCatalog['variant']->id,
                'price_unit_id' => $otherCatalog['unit']->id,
                'source_name' => 'Supplier Palsu',
                'recorded_by' => 999999,
                'price_type' => 'special',
            ])
            ->assertRedirect(route('mm05.prices.index'));

        $this->assertDatabaseHas('mm_material_prices', [
            'source_quote_id' => $quote->id,
            'material_variant_id' => $catalog['variant']->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '100.0000',
            'currency' => 'IDR',
            'source_name' => $supplier->name,
            'recorded_by' => $user->id,
            'price_type' => 'reference',
        ]);

        $this->assertDatabaseMissing('mm_material_prices', [
            'source_quote_id' => $quote->id,
            'amount' => '1.0000',
            'currency' => 'USD',
        ]);
    }

    public function test_pending_and_rejected_quotes_cannot_be_recorded(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();

        foreach ([
            MmSupplierQuote::STATUS_PENDING,
            MmSupplierQuote::STATUS_REJECTED,
        ] as $status) {
            $quote = $this->createQuote(
                $catalog['variant'],
                $supplier,
                $catalog['unit']
            );

            if ($status === MmSupplierQuote::STATUS_REJECTED) {
                $rejector = $this->createUserWithRole('ADMIN_BPM');

                $quote->forceFill([
                    'approval_status' => $status,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => $rejector->id,
                    'rejected_at' => now(),
                    'rejection_reason' => 'Penawaran ditolak untuk pengujian.',
                ])->save();
            }

            $this->actingAs($user)
                ->from(route('mm05.prices.index'))
                ->post(route('mm05.prices.store'), [
                    'source_quote_id' => $quote->id,
                    'effective_from' => '2026-11-09',
                ])
                ->assertSessionHasErrors('source_quote_id');

            $this->assertDatabaseMissing('mm_material_prices', [
                'source_quote_id' => $quote->id,
            ]);
        }
    }

    public function test_inactive_quote_and_supplier_are_rejected(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();

        $inactiveQuote = $this->createApprovedQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit']
        );

        $inactiveQuote->forceFill(['is_active' => false])->save();

        $inactiveSupplier = $this->createSupplier(false);

        $supplierQuote = $this->createApprovedQuote(
            $catalog['variant'],
            $inactiveSupplier,
            $catalog['unit']
        );

        foreach ([$inactiveQuote, $supplierQuote] as $quote) {
            $this->actingAs($user)
                ->from(route('mm05.prices.index'))
                ->post(route('mm05.prices.store'), [
                    'source_quote_id' => $quote->id,
                    'effective_from' => '2026-11-09',
                ])
                ->assertSessionHasErrors('source_quote_id');
        }
    }

    public function test_inactive_material_variant_and_unit_are_rejected(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');

        foreach (['mm_material_variants', 'mm_materials', 'mm_units'] as $table) {
            $catalog = $this->createCatalog();
            $supplier = $this->createSupplier();

            $quote = $this->createApprovedQuote(
                $catalog['variant'],
                $supplier,
                $catalog['unit']
            );

            $id = match ($table) {
                'mm_material_variants' => $catalog['variant']->id,
                'mm_materials' => $catalog['material']->id,
                'mm_units' => $catalog['unit']->id,
            };

            \DB::table($table)->where('id', $id)->update([
                'is_active' => false,
            ]);

            $this->actingAs($user)
                ->from(route('mm05.prices.index'))
                ->post(route('mm05.prices.store'), [
                    'source_quote_id' => $quote->id,
                    'effective_from' => '2026-11-09',
                ])
                ->assertSessionHasErrors('source_quote_id');
        }
    }

    public function test_effective_dates_must_fit_supplier_quote_validity(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();

        $quote = $this->createApprovedQuote(
            $catalog['variant'],
            $supplier,
            $catalog['unit'],
            ['valid_until' => '2026-11-20']
        );

        $invalidCases = [
            [
                'effective_from' => '2026-11-07',
                'effective_until' => '2026-11-15',
            ],
            [
                'effective_from' => '2026-11-09',
                'effective_until' => '2026-11-21',
            ],
            [
                'effective_from' => '2026-11-09',
                'effective_until' => null,
            ],
        ];

        foreach ($invalidCases as $dates) {
            $this->actingAs($user)
                ->from(route('mm05.prices.index'))
                ->post(route('mm05.prices.store'), [
                    'source_quote_id' => $quote->id,
                    ...$dates,
                ])
                ->assertSessionHasErrors('effective_from');
        }

        $this->assertDatabaseMissing('mm_material_prices', [
            'source_quote_id' => $quote->id,
        ]);
    }

    public function test_invalid_effective_date_range_is_rejected(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createApprovedQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit']
        );

        $this->actingAs($user)
            ->from(route('mm05.prices.index'))
            ->post(route('mm05.prices.store'), [
                'source_quote_id' => $quote->id,
                'effective_from' => '2026-11-15',
                'effective_until' => '2026-11-09',
            ])
            ->assertSessionHasErrors('effective_until');

        $this->assertDatabaseMissing('mm_material_prices', [
            'source_quote_id' => $quote->id,
        ]);
    }

    public function test_direct_price_updates_append_history_per_material_and_supplier(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplierA = $this->createSupplier();
        $supplierB = $this->createSupplier();
        $priceData = [
            'material_id' => $catalog['material']->id,
            'price_unit_id' => $catalog['unit']->id,
            'currency' => 'IDR',
        ];

        $this->actingAs($user)->post(route('mm05.prices.direct.store'), $priceData + [
            'supplier_id' => $supplierA->id,
            'amount' => '100.0000',
            'effective_from' => '2026-11-01',
            'communication_method' => 'WhatsApp',
            'communication_note' => 'Harga awal',
        ])->assertRedirect(route('mm05.prices.index'));

        $this->post(route('mm05.prices.direct.store'), $priceData + [
            'supplier_id' => $supplierB->id,
            'amount' => '120.0000',
            'currency' => 'USD',
            'effective_from' => '2026-11-02',
            'communication_method' => 'Email',
        ])->assertRedirect(route('mm05.prices.index'));

        $this->post(route('mm05.prices.direct.store'), $priceData + [
            'supplier_id' => $supplierA->id,
            'amount' => '110.0000',
            'effective_from' => '2026-11-03',
            'communication_method' => 'Telepon',
            'communication_note' => 'Harga diperbarui',
        ])->assertRedirect(route('mm05.prices.index'));

        $this->assertDatabaseHas('mm_material_prices', [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplierA->id,
            'amount' => '100.0000',
            'effective_until' => '2026-11-02',
            'change_reason' => 'Konfirmasi melalui WhatsApp. Harga awal',
        ]);
        $this->assertDatabaseHas('mm_material_prices', [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplierB->id,
            'amount' => '120.0000',
            'effective_until' => null,
            'change_reason' => 'Konfirmasi melalui Email.',
        ]);
        $this->assertDatabaseHas('mm_material_prices', [
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplierA->id,
            'amount' => '110.0000',
            'effective_until' => null,
            'change_reason' => 'Konfirmasi melalui Telepon. Harga diperbarui',
        ]);

        $this->get(route('mm05.prices.index'))->assertOk()
            ->assertSee('Harga Material')
            ->assertSee('Harga terbaru')
            ->assertSee('Riwayat harga')
            ->assertDontSee('Ringkasan')
            ->assertSee('Material')
            ->assertSee('Kategori')
            ->assertSee('Supplier')
            ->assertSee('Harga Material')
            ->assertSee($supplierA->name)
            ->assertSee($supplierB->name)
            ->assertDontSee('Price Bank')
            ->assertDontSee('Satuan</a>', false)
            ->assertDontSee('Penawaran</a>', false)
            ->assertDontSee('Penawaran Disetujui')
            ->assertDontSee('name="material_variant_id"', false);
    }

    public function test_material_conversion_is_snapshotted_and_normalized_per_material(): void
    {
        $catalog = $this->createCatalog();
        $catalog['unit']->update(['usage_group' => 'base']);
        $priceUnit = MmUnit::query()->create([
            'code' => 'UOM-BOX-'.Str::upper(Str::random(8)),
            'name' => 'Box',
            'usage_group' => 'price',
        ]);
        $supplier = $this->createSupplier();
        $procurement = $this->createUserWithRole('PROCUREMENT');
        $service = app(MmMaterialPriceService::class);

        $firstPrice = $service->updateSupplierPrice([
            'supplier_id' => $supplier->id,
            'price_unit_id' => $priceUnit->id,
            'conversion_factor' => '100',
            'amount' => '60000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-09',
        ], $catalog['variant']->id, $procurement->id);

        $secondPrice = $service->updateSupplierPrice([
            'supplier_id' => $supplier->id,
            'price_unit_id' => $priceUnit->id,
            'conversion_factor' => '200',
            'amount' => '60000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-12',
        ], $catalog['variant']->id, $procurement->id);

        $this->assertDatabaseHas('mm_material_unit_conversions', [
            'material_id' => $catalog['material']->id,
            'base_unit_id' => $catalog['unit']->id,
            'price_unit_id' => $priceUnit->id,
            'conversion_factor' => '200.00000000',
        ]);
        $this->assertDatabaseHas('mm_material_prices', [
            'id' => $firstPrice->id,
            'conversion_factor' => '100.00000000',
            'effective_until' => '2026-11-11',
        ]);
        $this->assertDatabaseHas('mm_material_prices', [
            'id' => $secondPrice->id,
            'conversion_factor' => '200.00000000',
            'effective_until' => null,
        ]);

        $historicalNormalizedPrice = app(MmReferencePriceSelector::class)
            ->latestEligiblePricesForVariants([$catalog['variant']->id], '2026-11-10')
            ->get($catalog['variant']->id)
            ->get('IDR|'.$priceUnit->id)
            ->sole();

        $this->assertSame('600.00000000', $historicalNormalizedPrice->amount_in_base_unit);

        $normalizedPrice = app(MmReferencePriceSelector::class)
            ->latestEligiblePricesForVariants([$catalog['variant']->id], '2026-11-13')
            ->get($catalog['variant']->id)
            ->get('IDR|'.$priceUnit->id)
            ->sole();

        $this->assertSame('300.00000000', $normalizedPrice->amount_in_base_unit);

        $otherCatalog = $this->createCatalog();
        $otherPrice = $service->updateSupplierPrice([
            'supplier_id' => $supplier->id,
            'price_unit_id' => $priceUnit->id,
            'amount' => '60000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-12',
        ], $otherCatalog['variant']->id, $procurement->id);
        $otherNormalizedPrice = app(MmReferencePriceSelector::class)
            ->latestEligiblePricesForVariants([$otherCatalog['variant']->id], '2026-11-13')
            ->get($otherCatalog['variant']->id)
            ->get('IDR|'.$priceUnit->id)
            ->sole();

        $this->assertNull($otherPrice->conversion_factor);
        $this->assertNull($otherNormalizedPrice->amount_in_base_unit);
        $this->assertDatabaseMissing('mm_material_unit_conversions', [
            'material_id' => $otherCatalog['material']->id,
            'price_unit_id' => $priceUnit->id,
        ]);
    }

    public function test_direct_price_rejects_non_price_uom_and_nonpositive_conversion(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $baseOnlyUnit = MmUnit::query()->create([
            'code' => 'UOM-BASE-'.$catalog['unit']->id,
            'name' => 'Base-only price rejection',
            'usage_group' => 'base',
        ]);
        $payload = [
            'material_id' => $catalog['material']->id,
            'supplier_id' => $supplier->id,
            'amount' => '100',
            'currency' => 'IDR',
            'effective_from' => '2026-11-09',
            'communication_method' => 'Email',
        ];

        $this->actingAs($user)
            ->post(route('mm05.prices.direct.store'), $payload + [
                'price_unit_id' => $baseOnlyUnit->id,
            ])
            ->assertSessionHasErrors('price_unit_id');

        $this->post(route('mm05.prices.direct.store'), $payload + [
            'price_unit_id' => $catalog['unit']->id,
            'conversion_factor' => '0',
        ])->assertSessionHasErrors('conversion_factor');

        $this->assertDatabaseMissing('mm_material_prices', [
            'material_variant_id' => $catalog['variant']->id,
        ]);
    }

    public function test_legacy_quote_page_redirects_without_deleting_historical_quote(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $quote = $this->createQuote($catalog['variant'], $this->createSupplier(), $catalog['unit']);

        $this->actingAs($user)->get(route('mm05.quotes.index'))
            ->assertRedirect(route('mm05.prices.index'));
        $this->assertDatabaseHas('mm_supplier_quotes', ['id' => $quote->id]);
    }

    public function test_existing_price_history_is_preserved(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();

        $quote = $this->createApprovedQuote(
            $catalog['variant'],
            $supplier,
            $catalog['unit']
        );

        $oldPrice = $this->createPrice(
            $catalog['variant'],
            $catalog['unit'],
            $quote,
            [
                'amount' => '90.0000',
                'effective_from' => '2026-11-01',
                'effective_until' => '2026-11-08',
            ]
        );

        $this->actingAs($user)
            ->post(route('mm05.prices.store'), [
                'source_quote_id' => $quote->id,
                'effective_from' => '2026-11-09',
                'effective_until' => '2026-11-20',
            ])
            ->assertRedirect(route('mm05.prices.index'));

        $this->assertDatabaseHas('mm_material_prices', [
            'id' => $oldPrice->id,
            'amount' => '90.0000',
            'effective_from' => '2026-11-01',
        ]);

        $this->assertDatabaseHas('mm_material_prices', [
            'source_quote_id' => $quote->id,
            'amount' => '100.0000',
            'effective_from' => '2026-11-09',
        ]);

        $this->assertSame(
            2,
            MmMaterialPrice::query()
                ->where('material_variant_id', $catalog['variant']->id)
                ->count()
        );
    }

    public function test_only_procurement_can_manage_material_prices(): void
    {
        $catalog = $this->createCatalog();
        $quote = $this->createApprovedQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit']
        );

        foreach (['ADMIN_BPM', 'ADMIN_PRODUKSI', 'ASST_MANAGER_PRODUKSI'] as $role) {
            $user = $this->createUserWithRole($role);

            $this->actingAs($user)
                ->post(route('mm05.prices.store'), [
                    'source_quote_id' => $quote->id,
                    'effective_from' => '2026-11-09',
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('mm_material_prices', [
            'source_quote_id' => $quote->id,
        ]);
    }

    public function test_guest_cannot_record_material_prices(): void
    {
        $catalog = $this->createCatalog();
        $quote = $this->createApprovedQuote(
            $catalog['variant'],
            $this->createSupplier(),
            $catalog['unit']
        );

        $this->post(route('mm05.prices.store'), [
            'source_quote_id' => $quote->id,
            'effective_from' => '2026-11-09',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('mm_material_prices', [
            'source_quote_id' => $quote->id,
        ]);
    }

    private function createCatalog(): array
    {
        $suffix = Str::upper(Str::random(8));

        $category = MmCategory::query()->create([
            'code' => 'CAT-P-'.$suffix,
            'name' => 'Price Category '.$suffix,
        ]);

        $unit = MmUnit::query()->create([
            'code' => 'UNIT-P-'.$suffix,
            'name' => 'Price Unit '.$suffix,
            'dimension' => 'count',
        ]);

        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-P-'.$suffix,
            'name' => 'Price Material '.$suffix,
        ]);

        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-P-'.$suffix,
            'variant_name' => 'Price Variant '.$suffix,
            'base_unit_id' => $unit->id,
        ]);

        return compact('category', 'unit', 'material', 'variant');
    }

    private function createSupplier(bool $isActive = true): MmSupplier
    {
        $suffix = Str::upper(Str::random(8));

        return MmSupplier::query()->create([
            'code' => 'SUP-P-'.$suffix,
            'name' => 'Price Supplier '.$suffix,
            'is_active' => $isActive,
        ]);
    }

    private function createQuote(
        MmMaterialVariant $variant,
        MmSupplier $supplier,
        MmUnit $unit,
        array $overrides = []
    ): MmSupplierQuote {
        return MmSupplierQuote::query()->create(array_merge([
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $unit->id,
            'amount' => '100.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-08',
        ], $overrides));
    }

    private function createApprovedQuote(
        MmMaterialVariant $variant,
        MmSupplier $supplier,
        MmUnit $unit,
        array $overrides = []
    ): MmSupplierQuote {
        $quote = $this->createQuote($variant, $supplier, $unit, $overrides);

        $approver = $this->createUserWithRole('ADMIN_BPM');

        $quote->forceFill([
            'approval_status' => MmSupplierQuote::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'is_active' => true,
        ])->save();

        return $quote->refresh();
    }

    private function createPrice(
        MmMaterialVariant $variant,
        MmUnit $unit,
        ?MmSupplierQuote $quote,
        array $overrides = []
    ): MmMaterialPrice {
        return MmMaterialPrice::query()->create(array_merge([
            'material_variant_id' => $variant->id,
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
            'name' => $roleCode.' Price Test '.$suffix,
            'email' => $suffix.'@price.test',
            'password' => Hash::make('test-password'),
        ]);

        $role = BpmRole::query()->firstOrCreate(
            ['code' => $roleCode],
            ['name' => str_replace('_', ' ', $roleCode)]
        );

        $user->roles()->attach($role);

        return $user;
    }
}
