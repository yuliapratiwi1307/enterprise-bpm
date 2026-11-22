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

class MmReferencePriceSelectorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_direct_procurement_price_is_eligible_for_reference_price_selection(): void
    {
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $procurement = $this->createUserWithRole('PROCUREMENT');

        $price = app(MmMaterialPriceService::class)->updateSupplierPrice([
            'supplier_id' => $supplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '12500.0000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-09',
            'change_reason' => 'Harga langsung Procurement',
        ], $catalog['variant']->id, $procurement->id);

        $selected = app(MmReferencePriceSelector::class)
            ->latestEligiblePricesForVariants([$catalog['variant']->id], '2026-11-11');

        $this->assertTrue($selected->has($catalog['variant']->id));
        $this->assertTrue($selected[$catalog['variant']->id]->has('IDR|'.$catalog['unit']->id));
        $this->assertSame(
            $price->id,
            $selected[$catalog['variant']->id]['IDR|'.$catalog['unit']->id]->sole()->id,
        );
        $this->assertNull($selected[$catalog['variant']->id]['IDR|'.$catalog['unit']->id]->sole()->source_quote_id);
        $this->assertSame(
            '12500.00000000',
            $selected[$catalog['variant']->id]['IDR|'.$catalog['unit']->id]->sole()->amount_in_base_unit,
        );
    }

    public function test_direct_procurement_price_from_inactive_supplier_is_not_eligible(): void
    {
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $procurement = $this->createUserWithRole('PROCUREMENT');

        $price = app(MmMaterialPriceService::class)->updateSupplierPrice([
            'supplier_id' => $supplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '12500.0000',
            'currency' => 'IDR',
            'effective_from' => '2026-11-09',
        ], $catalog['variant']->id, $procurement->id);

        $supplier->forceFill(['is_active' => false])->save();

        $selected = app(MmReferencePriceSelector::class)
            ->latestEligiblePricesForVariants([$catalog['variant']->id], '2026-11-11');

        $this->assertFalse($selected->has($catalog['variant']->id));
        $this->assertDatabaseHas('mm_material_prices', ['id' => $price->id]);
    }

    public function test_quote_based_price_remains_eligible_only_when_approved_and_supplier_active(): void
    {
        $catalog = $this->createCatalog();
        $supplier = $this->createSupplier();
        $approver = $this->createUserWithRole('ADMIN_BPM');

        $quote = MmSupplierQuote::query()->create([
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'amount' => '10000.0000',
            'currency' => 'IDR',
            'quoted_on' => '2026-11-08',
        ]);
        $quote->forceFill([
            'approval_status' => MmSupplierQuote::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'is_active' => true,
        ])->save();

        $price = MmMaterialPrice::query()->create([
            'material_variant_id' => $catalog['variant']->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $catalog['unit']->id,
            'source_quote_id' => $quote->id,
            'price_type' => 'reference',
            'amount' => $quote->amount,
            'currency' => $quote->currency,
            'effective_from' => '2026-11-09',
            'source_name' => $supplier->name,
        ]);

        $selected = app(MmReferencePriceSelector::class)
            ->latestEligiblePricesForVariants([$catalog['variant']->id], '2026-11-11');

        $this->assertSame(
            $price->id,
            $selected[$catalog['variant']->id]['IDR|'.$catalog['unit']->id]->sole()->id,
        );
    }

    private function createCatalog(): array
    {
        $suffix = Str::upper(Str::random(8));
        $category = MmCategory::query()->create([
            'code' => 'CAT-RP-'.$suffix,
            'name' => 'Reference Price Category '.$suffix,
        ]);
        $unit = MmUnit::query()->create([
            'code' => 'UNIT-RP-'.$suffix,
            'name' => 'Reference Price Unit '.$suffix,
            'dimension' => 'count',
        ]);
        $material = MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => 'MAT-RP-'.$suffix,
            'name' => 'Reference Price Material '.$suffix,
        ]);
        $variant = MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => 'SKU-RP-'.$suffix,
            'variant_name' => 'Reference Price Variant '.$suffix,
            'base_unit_id' => $unit->id,
        ]);

        return compact('category', 'unit', 'material', 'variant');
    }

    private function createSupplier(): MmSupplier
    {
        $suffix = Str::upper(Str::random(8));

        return MmSupplier::query()->create([
            'code' => 'SUP-RP-'.$suffix,
            'name' => 'Reference Price Supplier '.$suffix,
            'is_active' => true,
        ]);
    }

    private function createUserWithRole(string $roleCode): User
    {
        $suffix = Str::lower(Str::random(8));
        $user = User::query()->create([
            'name' => $roleCode.' Reference Price '.$suffix,
            'email' => $suffix.'@reference-price.test',
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
