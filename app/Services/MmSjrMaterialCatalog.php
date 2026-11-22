<?php

namespace App\Services;

use App\Models\MmMaterialVariant;
use Illuminate\Validation\ValidationException;

class MmSjrMaterialCatalog
{
    public function options(): array
    {
        $variants = MmMaterialVariant::query()
            ->with(['material', 'baseUnit', 'attributes.definition', 'prices.priceUnit', 'prices.supplier', 'prices.sourceQuote.supplier'])
            ->where('is_active', true)
            ->whereHas('material', fn ($q) => $q->where('is_active', true))
            ->orderBy('material_id')->orderBy('id')->get();

        $prices = app(MmReferencePriceSelector::class)->latestEligiblePricesForVariants(
            $variants->pluck('id')->map(fn ($id) => (int) $id)->all(), now()->toDateString()
        );

        return $variants->map(function ($variant) use ($prices) {
            $option = $this->snapshot($variant);
            $candidates = $prices->get($variant->id, collect())->flatten(1)
                ->filter(fn ($p) => $p->currency === 'IDR')
                ->sortByDesc('effective_from');
            $price = $candidates->first();

            // conversion_factor = number of base units represented by one price unit.
            // Do not fabricate a base-unit price if the conversion is unknown.
            $factor = $price?->conversion_factor;
            $baseUnitPrice = ($price && $factor !== null && (float) $factor > 0)
                ? ((float) $price->amount / (float) $factor)
                : null;

            $option['reference_price'] = $baseUnitPrice;
            $option['price_amount'] = $price ? (float) $price->amount : null;
            $option['price_unit'] = $price?->priceUnit?->name ?? $price?->priceUnit?->code;
            $option['price_conversion_factor'] = $factor !== null ? (float) $factor : null;
            $option['price_currency'] = $price?->currency;
            $option['price_vendor'] = $price?->supplier?->name ?? $price?->sourceQuote?->supplier?->name ?? $price?->source_name;
            $option['price_valid_from'] = $price?->effective_from?->format('Y-m-d');
            $option['price_valid_until'] = $price?->effective_until?->format('Y-m-d');
            $option['price_note'] = $price
                ? ($baseUnitPrice !== null ? 'Harga dinormalisasi ke satuan dasar MM-05.' : 'Konversi satuan harga belum tersedia; biaya terpakai belum dapat dihitung dengan aman.')
                : 'Harga referensi IDR yang memenuhi syarat belum tersedia.';
            return $option;
        })->all();
    }

    public function snapshot(MmMaterialVariant $v): array
    {
        $attributes = $v->attributes->map(function ($a) {
            $label = $a->definition?->label ?? ('Atribut '.$a->attribute_definition_id);
            $value = $a->value_text ?? $a->value_number ?? ($a->value_boolean === null ? null : ($a->value_boolean ? 'Ya' : 'Tidak'));
            return trim($label.': '.(string) $value);
        })->filter()->implode('; ');
        $spec = implode('; ', array_filter([$v->specification_note, $attributes]));
        [$length, $width] = $this->sheetDimensions($v);
        // Data spesifikasi terstruktur untuk tampilan SJR; nilai asli MM-05 tidak diubah.
        $specificationAttributes = $v->attributes->map(function ($a) {
            $definition = $a->definition;
            $value = $a->value_text ?? $a->value_number ?? ($a->value_boolean === null ? null : ($a->value_boolean ? 'Ya' : 'Tidak'));
            if ($value === null || $value === '') return null;
            return [
                'name' => (string) ($definition?->label ?? ('Atribut '.$a->attribute_definition_id)),
                'value' => (string) $value,
                'unit' => (string) ($definition?->unit?->name ?? $definition?->unit?->code ?? ''),
            ];
        })->filter()->values()->all();
        return [
            'id' => (int) $v->id,
            'name' => (string) $v->material->name,
            'label' => trim($v->material->name.' · '.($v->variant_name ?: $v->sku ?: '#'.$v->id)),
            'sku' => (string) ($v->sku ?? ''),
            'spec' => $spec ?: (string) ($v->variant_name ?? ''),
            'unit' => (string) ($v->baseUnit?->code ?: $v->baseUnit?->name ?: ''),
            'unit_label' => (string) ($v->baseUnit?->name ?: $v->baseUnit?->code ?: ''),
            'specification_attributes' => $specificationAttributes,
            'sheet_length_cm' => $length,
            'sheet_width_cm' => $width,
        ];
    }

    private function sheetDimensions(MmMaterialVariant $v): array
    {
        $length = $width = null;
        $legacy = [];
        foreach ($v->attributes as $attribute) {
            $value = $attribute->value_number ?? $attribute->value_text;
            if (!is_numeric($value) || (float) $value <= 0) continue;
            $label = mb_strtolower((string) ($attribute->definition?->label ?? ''));
            $id = (int) $attribute->attribute_definition_id;
            if (preg_match('/panjang|length/', $label)) $length = (float) $value;
            elseif (preg_match('/lebar|width/', $label)) $width = (float) $value;
            if ($id === 7 || $id === 8) $legacy[$id] = (float) $value;
        }
        $length ??= $legacy[7] ?? null;
        $width ??= $legacy[8] ?? null;
        if ($length !== null && $width !== null) return [$length, $width];
        $text = trim((string) ($v->variant_name ?? '').' '.(string) ($v->specification_note ?? ''));
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*[x×]\s*(\d+(?:[.,]\d+)?)/iu', $text, $m))
            return [(float) str_replace(',', '.', $m[1]), (float) str_replace(',', '.', $m[2])];
        return [null, null];
    }

    public function apply(array $rows): array
    {
        $ids = collect($rows)->pluck('material_variant_id')->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)->unique()->all();
        $variants = MmMaterialVariant::query()->with(['material', 'baseUnit', 'attributes.definition'])
            ->whereIn('id', $ids)->where('is_active', true)
            ->whereHas('material', fn ($q) => $q->where('is_active', true))->get()->keyBy('id');
        foreach ($rows as &$row) {
            $id = $row['material_variant_id'] ?? null;
            if ($id === null || $id === '') continue; // Keep legacy free-text drafts editable.
            $variant = $variants->get((int) $id);
            if (!$variant) throw ValidationException::withMessages(['materials' => 'Varian MM-05 tidak aktif atau tidak ditemukan.']);
            $master = $this->snapshot($variant);
            if ($master['unit'] === '') throw ValidationException::withMessages(['materials' => 'Satuan dasar MM-05 belum tersedia.']);
            $row['material_variant_id'] = $master['id'];
            $row['name'] = $master['name'];
            $row['spec'] = $master['spec'];
            $row['unit'] = $master['unit'];
            $row['mm05_snapshot'] = $master;
        }
        unset($row);
        return $rows;
    }
}
