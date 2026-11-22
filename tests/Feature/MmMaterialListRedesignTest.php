<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmMaterial;
use App\Models\MmMaterialPrice;
use App\Models\MmMaterialVariant;
use App\Models\MmSupplier;
use App\Models\MmUnit;
use App\Models\MmVariantAttribute;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MmMaterialListRedesignTest extends TestCase
{
    use DatabaseTransactions;

    public function test_workspace_opens_materials_with_sidebar_navigation_and_active_page_indicator(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');

        $workspaceResponse = $this->actingAs($user)->get(route('mm05.workspace'));

        $workspaceResponse->assertOk()
            ->assertSee('Daftar Material')
            ->assertSee('Master Material')
            ->assertSee('Kategori')
            ->assertSee('Supplier')
            ->assertSee('Harga Material');

        $workspaceHtml = $workspaceResponse->getContent();
        $this->assertStringNotContainsString('mm05-global-nav', $workspaceHtml);
        $this->assertStringContainsString('class="mm05-subnav"', $workspaceHtml);
        $this->assertActiveMaterialNavigationLink($workspaceResponse, 'mm05.materials.index');

        foreach ([
            'mm05.categories.index' => 'Kategori',
            'mm05.suppliers.index' => 'Supplier',
            'mm05.prices.index' => 'Harga Material',
        ] as $routeName => $label) {
            $response = $this->get(route($routeName));

            $response->assertOk();
            $response->assertSee($label);
            $this->assertActiveMaterialNavigationLink($response, $routeName);
        }
    }

    public function test_material_list_shows_eight_columns_specs_latest_supplier_price_and_detail_action(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = MmCategory::query()->create(['code' => 'CAT-LIST-'.Str::upper(Str::random(6)), 'name' => 'Bahan Baku Produksi']);
        $kilogram = MmUnit::query()->create(['code' => 'KG-'.Str::upper(Str::random(4)), 'name' => 'kg', 'dimension' => 'mass']);
        $millimeter = MmUnit::query()->create(['code' => 'MM-'.Str::upper(Str::random(4)), 'name' => 'mm', 'dimension' => 'length']);
        $material = $this->createMaterial($category, 'MAT-SS304', 'Stainless Steel SS304', true);
        $variant = $this->createVariant($material, $kilogram, 'SKU-SS304');

        foreach ([
            ['GRADE', 'Grade', 'text', null, 'SS304', null],
            ['THICKNESS', 'Ketebalan', 'number', $millimeter->id, null, '3'],
            ['WIDTH', 'Lebar', 'number', $millimeter->id, null, '1220'],
            ['LENGTH', 'Panjang', 'number', $millimeter->id, null, '2440'],
            ['FINISH', 'Finishing', 'text', null, 'No. 4', null],
        ] as $index => [$code, $label, $type, $unitId, $text, $number]) {
            $definition = MmAttributeDefinition::query()->create([
                'category_id' => $category->id,
                'code' => $code,
                'label' => $label,
                'data_type' => $type,
                'unit_id' => $unitId,
                'sort_order' => $index + 1,
            ]);
            MmVariantAttribute::query()->create([
                'variant_id' => $variant->id,
                'attribute_definition_id' => $definition->id,
                'value_text' => $text,
                'value_number' => $number,
            ]);
        }

        $olderSupplier = $this->createSupplier('Supplier Lama');
        $latestSupplier = $this->createSupplier('Supplier Terbaru');
        $this->createPrice($variant, $olderSupplier, $kilogram, '40000', now()->subDays(3)->toDateString());
        $latestPrice = $this->createPrice($variant, $latestSupplier, $kilogram, '45000', now()->subDays(1)->toDateString());

        $noDataMaterial = $this->createMaterial($category, 'MAT-NODATA', 'Material Tanpa Data', true);
        $inactiveMaterial = $this->createMaterial($category, 'MAT-INACTIVE', 'Material Nonaktif', false);
        $otherCategory = MmCategory::query()->create(['code' => 'CAT-OTHER-'.Str::upper(Str::random(6)), 'name' => 'Kategori Lain']);
        $otherMaterial = $this->createMaterial($otherCategory, 'MAT-OTHER', 'Material Lain', true);
        $otherVariant = $this->createVariant($otherMaterial, $kilogram, 'SKU-OTHER');
        $otherDefinition = MmAttributeDefinition::query()->create([
            'category_id' => $otherCategory->id,
            'code' => 'OTHER-ONLY',
            'label' => 'Atribut Khusus Lain',
            'data_type' => 'text',
        ]);
        MmVariantAttribute::query()->create([
            'variant_id' => $otherVariant->id,
            'attribute_definition_id' => $otherDefinition->id,
            'value_text' => 'JANGAN TERCAMPUR',
        ]);

        $relationQueryCounts = ['mm_material_prices' => 0, 'mm_variant_attributes' => 0];
        DB::listen(function (QueryExecuted $query) use (&$relationQueryCounts): void {
            $sql = strtolower($query->sql);
            foreach (array_keys($relationQueryCounts) as $table) {
                if (str_contains($sql, $table)) {
                    $relationQueryCounts[$table]++;
                }
            }
        });

        $response = $this->actingAs($user)->get(route('mm05.materials.index'));
        $this->assertSame(1, $relationQueryCounts['mm_material_prices']);
        $this->assertSame(1, $relationQueryCounts['mm_variant_attributes']);
        $response->assertOk()
            ->assertSee('Grade:')
            ->assertSee('SS304')
            ->assertSee('Ketebalan:')
            ->assertSee('3 mm')
            ->assertSee('Lebar:')
            ->assertSee('1.220 mm')
            ->assertSee('Panjang:')
            ->assertSee('2.440 mm')
            ->assertSee('+ 1 lainnya')
            ->assertSee('Supplier Terbaru')
            ->assertDontSee('Supplier Lama')
            ->assertSee('Rp45.000')
            ->assertSee('/ kg')
            ->assertSee($latestPrice->effective_from->format('d/m/Y'))
            ->assertSee('Belum ada spesifikasi')
            ->assertSee('Belum ada harga')
            ->assertSee('Aktif')
            ->assertSee('Nonaktif')
            ->assertSee(route('mm05.materials.show', $material->id))
            ->assertSee('aria-label="Detail material: Stainless Steel SS304"', false)
            ->assertSee('Tambah Material')
            ->assertSee('Cari kode atau nama')
            ->assertSee('Status');

        $html = $response->getContent();
        preg_match_all('/<th[^>]*>(.*?)<\/th>/s', $html, $headerMatches);
        $headers = array_map(fn (string $header): string => trim(strip_tags($header)), $headerMatches[1]);
        $this->assertSame([
            'Nama Material',
            'Spesifikasi Material',
            'Supplier',
            'Status',
            'Update Harga',
            'Harga',
            'Action',
        ], $headers);

        preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $bodyMatch);
        $this->assertSame(4, substr_count($bodyMatch[1] ?? '', '<tr'));
        preg_match_all('/<tr>(.*?)<\/tr>/s', $bodyMatch[1] ?? '', $rowMatches);
        $materialRow = collect($rowMatches[1])->first(fn (string $row): bool => str_contains($row, 'MAT-SS304'));
        $this->assertNotNull($materialRow);
        preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $materialRow, $cellMatches);
        $specificationCell = $cellMatches[1][1] ?? '';
        $this->assertStringNotContainsString('Bahan Baku Produksi', $materialRow);
        $this->assertStringNotContainsString('JANGAN TERCAMPUR', $specificationCell);
        // Procurement melihat tombol Detail dan Nonaktifkan.
        $this->assertSame(2, substr_count($materialRow, 'class="mm-detail-action"'));
        $this->assertStringContainsString('method="POST"', $materialRow);
        $this->assertStringContainsString('Nonaktifkan material', $materialRow);
        $this->assertStringNotContainsString('>Edit<', $materialRow);

        $noPriceRow = collect($rowMatches[1])->first(fn (string $row): bool => str_contains($row, 'MAT-NODATA'));
        $this->assertNotNull($noPriceRow);
        preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $noPriceRow, $noPriceCells);
        $this->assertSame('-', trim(strip_tags($noPriceCells[1][2] ?? '')));

        $detailResponse = $this->get(route('mm05.materials.show', $material->id));
        $detailResponse->assertOk()->assertSee('Stainless Steel SS304');

        $this->assertNotNull($noDataMaterial->id);
        $this->assertNotNull($inactiveMaterial->id);
    }

    public function test_material_search_and_status_filters_still_work_without_duplicate_rows(): void
    {
        $user = $this->createUserWithRole('PROCUREMENT');
        $category = MmCategory::query()->create(['code' => 'CAT-FILTER-'.Str::upper(Str::random(6)), 'name' => 'Filter Category']);
        $unit = MmUnit::query()->create(['code' => 'UNIT-FILTER-'.Str::upper(Str::random(4)), 'name' => 'unit', 'dimension' => 'count']);
        $active = $this->createMaterial($category, 'MAT-FILTER-ACTIVE', 'Active Filter Material', true);
        $this->createVariant($active, $unit, 'SKU-FILTER-ACTIVE');
        $inactive = $this->createMaterial($category, 'MAT-FILTER-INACTIVE', 'Inactive Filter Material', false);
        $this->createVariant($inactive, $unit, 'SKU-FILTER-INACTIVE');

        $activeResponse = $this->actingAs($user)->get(route('mm05.materials.index', ['q' => 'FILTER', 'status' => 'active']));
        $activeResponse->assertOk()->assertSee('Active Filter Material')->assertDontSee('Inactive Filter Material');
        preg_match('/<tbody>(.*?)<\/tbody>/s', $activeResponse->getContent(), $activeBody);
        $this->assertSame(1, substr_count($activeBody[1] ?? '', '<tr'));

        $inactiveResponse = $this->get(route('mm05.materials.index', ['q' => 'FILTER', 'status' => 'inactive']));
        $inactiveResponse->assertOk()->assertSee('Inactive Filter Material')->assertDontSee('Active Filter Material');
        preg_match('/<tbody>(.*?)<\/tbody>/s', $inactiveResponse->getContent(), $inactiveBody);
        $this->assertSame(1, substr_count($inactiveBody[1] ?? '', '<tr'));
    }

    private function createMaterial(MmCategory $category, string $code, string $name, bool $active): MmMaterial
    {
        return MmMaterial::query()->create([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $name,
            'is_active' => $active,
        ]);
    }

    private function assertActiveMaterialNavigationLink(TestResponse $response, string $routeName): void
    {
        preg_match_all('/<a class="nav-link mm05-subnav-link" href="([^"]+)" aria-current="page"\s*>/', $response->getContent(), $activeLinks);

        $this->assertCount(1, $activeLinks[1]);
        $this->assertSame(route($routeName), $activeLinks[1][0]);
    }

    private function createVariant(MmMaterial $material, MmUnit $unit, string $sku): MmMaterialVariant
    {
        return MmMaterialVariant::query()->create([
            'material_id' => $material->id,
            'sku' => $sku,
            'variant_name' => $material->name,
            'base_unit_id' => $unit->id,
        ]);
    }

    private function createSupplier(string $name): MmSupplier
    {
        return MmSupplier::query()->create([
            'code' => 'SUP-LIST-'.Str::upper(Str::random(8)),
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function createPrice(MmMaterialVariant $variant, MmSupplier $supplier, MmUnit $unit, string $amount, string $effectiveFrom): MmMaterialPrice
    {
        return MmMaterialPrice::query()->create([
            'material_variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'price_unit_id' => $unit->id,
            'price_type' => 'reference',
            'amount' => $amount,
            'currency' => 'IDR',
            'effective_from' => $effectiveFrom,
        ]);
    }

    private function createUserWithRole(string $roleCode): User
    {
        $suffix = Str::lower(Str::random(8));
        $user = User::query()->create([
            'name' => $roleCode.' Material List '.$suffix,
            'email' => $suffix.'@material-list.test',
            'password' => Hash::make('test-password'),
        ]);
        $role = BpmRole::query()->firstOrCreate(['code' => $roleCode], ['name' => str_replace('_', ' ', $roleCode)]);
        $user->roles()->attach($role);

        return $user;
    }
}
