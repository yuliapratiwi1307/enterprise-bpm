<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** SJR V5.8.1: simplified per-item technical draft. Legacy leadtime data is preserved. */
class SjrMultiItemTechnicalDraftController extends Controller
{
    public function save(Request $request, string $jobNumber, int $itemId)
    {
        abort_unless(config('database.default') === 'pgsql' && (DB::connection()->getDatabaseName() === 'enterprise_bpm_browser' || (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test')), 403, 'Hanya database pengujian SJR.');
        abort_unless(Schema::hasTable('sjr_multi_item_technical_drafts') && Schema::hasTable('sjr_multi_item_brief_reviews'), 503, 'Migrasi V5.7.2 belum dijalankan.');
        abort_unless($request->user() && $request->user()->roles()->whereIn('code', ['ASST_MANAGER_PRODUKSI', 'ASSISTANT_MANAGER_PRODUKSI'])->exists(), 403, 'Hanya Asst. Manager Produksi dapat menyimpan draft teknis.');
        $input = $request->validate([
            'materials' => ['nullable', 'array', 'max:30'],
            'materials.*.material_variant_id' => ['nullable', 'integer', 'min:1'],
            'materials.*.source_type' => ['nullable', 'in:mm05_existing,new_material_request'],
            'materials.*.request_reason' => ['nullable', 'string', 'max:1000'],
            'materials.*.existing_attachments' => ['nullable', 'string', 'max:20000'],
            'materials.*.attachments' => ['nullable', 'array', 'max:5'],
            'materials.*.attachments.*' => ['file', 'max:204800', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'materials.*.name' => ['nullable', 'string', 'max:180'],
            'materials.*.spec' => ['nullable', 'string', 'max:5000'],
            'materials.*.category' => ['nullable', 'in:sheet,profile,pipe,cable,liquid,hardware,other'],
            'materials.*.attributes' => ['nullable', 'array', 'max:15'],
            'materials.*.attributes.*' => ['nullable', 'string', 'max:180'],
            'materials.*.custom_attributes' => ['nullable', 'array', 'max:10'],
            'materials.*.custom_attributes.*.name' => ['required', 'string', 'max:80'],
            'materials.*.custom_attributes.*.value' => ['required', 'string', 'max:180'],
            'materials.*.unit' => ['nullable', 'string', 'max:50'],
            'materials.*.cut_sizes' => ['nullable', 'array', 'max:30'],
            'materials.*.cut_sizes.*.length_cm' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'materials.*.cut_sizes.*.width_cm' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'materials.*.cut_sizes.*.quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'materials.*.cut_settings' => ['nullable', 'array'],
            'materials.*.cut_settings.kerf_cm' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'materials.*.cut_settings.edge_margin_cm' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'materials.*.cut_settings.join_mode' => ['nullable', 'in:cut,join'], // MM05_JOIN_MANUAL_V1
            'materials.*.cut_layout_json' => ['nullable', 'string', 'max:500000'],
            'materials.*.quantity' => ['nullable', 'numeric', 'gt:0', 'max:999999999'],
            'processes' => ['nullable', 'array', 'max:30'],
            'processes.*.name' => ['nullable', 'string', 'max:180'],
            'processes.*.spec' => ['nullable', 'string', 'max:500'],
            'processes.*.unit' => ['nullable', 'string', 'max:50'],
            'processes.*.quantity' => ['nullable', 'numeric', 'gt:0', 'max:999999999'],
            'processes.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'leadtime_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'leadtime_note' => ['nullable', 'string', 'max:3000'],
        ]);
        $normalize = function(array $rows, bool $withPricing=false) {
            $out = [];
            foreach ($rows as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $spec = trim((string) ($row['spec'] ?? ''));
                $unit = trim((string) ($row['unit'] ?? ''));
                $qty = $row['quantity'] ?? null;
                // SJR_SAVE_DRAFT_FINAL: kuantitas MM-05 Multi-Size berasal dari jumlah potongan,
                // bukan dari input legacy yang disembunyikan pada form.
                $isMultiSize = !$withPricing
                    && !empty($row['material_variant_id'])
                    && in_array(($row['category'] ?? ''), ['profile', 'sheet'], true)
                    && !empty($row['cut_sizes']) && is_array($row['cut_sizes']);
                if ($isMultiSize) {
                    $derivedCount = 0;
                    foreach ($row['cut_sizes'] as $cut) {
                        $pieceCount = $cut['quantity'] ?? null;
                        if (!is_numeric($pieceCount) || (int)$pieceCount != $pieceCount || (int)$pieceCount < 1) {
                            throw ValidationException::withMessages(['materials' => 'Jumlah potongan Multi-Size wajib berupa bilangan bulat positif.']);
                        }
                        $derivedCount += (int)$pieceCount;
                    }
                    $qty = $derivedCount;
                }
                if ($name === '' && $spec === '' && $unit === '' && ($qty === null || $qty === '')) {
                    continue;
                }
                if ($name === '' || $qty === null || $qty === '') {
                    throw ValidationException::withMessages(['materials' => 'Setiap baris material/proses yang diisi membutuhkan nama dan jumlah positif.']);
                }
                $entry = ['name' => $name, 'spec' => $spec, 'unit' => $unit, 'quantity' => (float) $qty];
                $sourceType = $row['source_type'] ?? null;
                if ($sourceType === 'new_material_request') {
                    if (!empty($row['material_variant_id'])) throw ValidationException::withMessages(['materials' => 'Material usulan tidak boleh memiliki ID Master Material MM-05.']);
                    $entry['source_type'] = 'new_material_request';
                    $entry['attachments'] = array_values($row['_attachments'] ?? []);
                    if ($spec === '') throw ValidationException::withMessages(['materials' => 'Spesifikasi material baru wajib diisi.']);
                } elseif (!empty($row['material_variant_id'])) { $entry['source_type'] = 'mm05_existing'; }
                if (!$withPricing && $sourceType !== 'new_material_request') {
                    $category = $row['category'] ?? 'other';
                    $allowed = [
                        'sheet' => ['thickness_mm', 'length_cm', 'width_cm'],
                        'profile' => ['profile_size', 'wall_mm', 'length_m'],
                        'pipe' => ['diameter', 'length_m'],
                        'cable' => ['cable_type', 'length_m'],
                        'liquid' => ['variant', 'volume_l'],
                        'hardware' => ['type', 'length_mm'],
                        'other' => ['description'],
                    ];
                    $attributes = [];
                    foreach (($row['attributes'] ?? []) as $key => $value) {
                        if (in_array($key, $allowed[$category] ?? [], true) && trim((string) $value) !== '') {
                            $attributes[$key] = trim((string) $value);
                        }
                    }

                    // MM05_BAR_V1: simpan kebutuhan potong 1D tanpa mengubah jalur sheet.
                    if ($category === 'profile' && !empty($row['cut_sizes'])) {
                        $settings = $row['cut_settings'] ?? [];
                        $kerf = (float) ($settings['kerf_cm'] ?? 0);
                        $margin = (float) ($settings['edge_margin_cm'] ?? 0);
                        if (!is_finite($kerf) || $kerf < 0 || $kerf > 5 || !is_finite($margin) || $margin < 0 || $margin > 50) {
                            throw ValidationException::withMessages(['materials' => 'Kerf atau margin batang tidak valid.']);
                        }
                        $cuts = []; $count = 0;
                        foreach ($row['cut_sizes'] as $cut) {
                            $p = (float) ($cut['length_cm'] ?? 0);
                            $q = (int) ($cut['quantity'] ?? 0);
                            if (!is_finite($p) || $p <= 0 || $q < 1 || $q > 100000) {
                                throw ValidationException::withMessages(['materials' => 'Panjang dan jumlah potongan batang harus positif.']);
                            }
                            $count += $q;
                            if ($count > 2500) throw ValidationException::withMessages(['materials' => 'Maksimum 2.500 potongan batang.']);
                            $cuts[] = ['length_cm' => $p, 'quantity' => $q];
                        }
                        $entry['cut_sizes'] = $cuts;
                        $mode = 'join'; // MM05_JOIN_AUTO_V2
                        if (!in_array($mode, ['cut', 'join'], true)) {
                            throw ValidationException::withMessages(['materials' => 'Mode sambungan batang tidak valid.']);
                        }
                        $entry['cut_settings'] = ['kerf_cm' => $kerf, 'edge_margin_cm' => $margin, 'join_mode' => $mode];
                        $entry['quantity'] = (float) $count;
                    }
                    if ($category === 'sheet') {
                        $settings = $row['cut_settings'] ?? [];
                        $kerf = isset($settings['kerf_cm']) && $settings['kerf_cm'] !== '' ? (float) $settings['kerf_cm'] : 0.0;
                        $margin = isset($settings['edge_margin_cm']) && $settings['edge_margin_cm'] !== '' ? (float) $settings['edge_margin_cm'] : 0.0;
                        if (! is_finite($kerf) || $kerf < 0 || $kerf > 5 || ! is_finite($margin) || $margin < 0 || $margin > 50) {
                            throw ValidationException::withMessages(['materials' => 'Pengaturan kerf atau margin tepi tidak valid.']);
                        }
                        $entry['cut_settings'] = ['kerf_cm' => $kerf, 'edge_margin_cm' => $margin];
                    }
                    if ($category === 'sheet' && !empty($row['cut_sizes'])) {
                        $cuts = [];
                        $totalQty = 0;
                        $totalArea = 0.0;
                        foreach ($row['cut_sizes'] as $cut) {
                            $length = (float) ($cut['length_cm'] ?? 0);
                            $width = (float) ($cut['width_cm'] ?? 0);
                            $count = (int) ($cut['quantity'] ?? 0);
                            if (!is_finite($length) || !is_finite($width) || $length <= 0 || $width <= 0 || $count < 1 || $count > 100000) {
                                throw ValidationException::withMessages(['materials' => 'Ukuran dan jumlah Multi-Size tidak valid.']);
                            }
                            $totalQty += $count;
                            $totalArea += $length * $width * $count;
                            $cuts[] = ['length_cm' => $length, 'width_cm' => $width, 'quantity' => $count];
                        }
                        if ($totalQty > 100000 || !is_finite($totalArea)) {
                            throw ValidationException::withMessages(['materials' => 'Total kebutuhan Multi-Size terlalu besar.']);
                        }
                        $entry['cut_sizes'] = $cuts;
                        $entry['cut_summary'] = ['total_pieces' => $totalQty, 'used_area_cm2' => round($totalArea, 4)];
                        $layoutRaw = $row['cut_layout_json'] ?? '';
                        if (is_string($layoutRaw) && trim($layoutRaw) !== '') {
                            try { $layout = json_decode($layoutRaw, true, 64, JSON_THROW_ON_ERROR); }
                            catch (\JsonException $e) { throw ValidationException::withMessages(['materials' => 'Data susunan potongan manual tidak valid.']); }
                            $master = $row['mm05_snapshot'] ?? [];
                            $stockW = (float) ($master['sheet_length_cm'] ?? 0);
                            $stockH = (float) ($master['sheet_width_cm'] ?? 0);
                            $kerf = (float) ($row['cut_settings']['kerf_cm'] ?? 0);
                            $margin = (float) ($row['cut_settings']['edge_margin_cm'] ?? 0);
                            if (!is_array($layout) || (float)($layout['stock_width'] ?? 0) !== $stockW || (float)($layout['stock_height'] ?? 0) !== $stockH || $stockW <= 0 || $stockH <= 0 || !is_array($layout['pieces'] ?? null) || count($layout['pieces']) > 2500) {
                                throw ValidationException::withMessages(['materials' => 'Ukuran master atau daftar susunan manual tidak sesuai dengan MM-05.']);
                            }
                            $pieces = $layout['pieces'];
                            $areaByGroup = [];
                            foreach ($pieces as $piece) {
                                $group = (int) ($piece['group'] ?? 0);
                                $sheet = (int) ($piece['sheet'] ?? -1);
                                $x = (float) ($piece['x'] ?? -1); $y = (float) ($piece['y'] ?? -1);
                                $w = (float) ($piece['w'] ?? 0); $h = (float) ($piece['h'] ?? 0);
                                $baseW = (float) ($piece['base_w'] ?? 0); $baseH = (float) ($piece['base_h'] ?? 0);
                                $validOrientation = ($baseW > 0 && $baseH > 0 && ((abs($w - $baseW) < 0.0001 && abs($h - $baseH) < 0.0001) || (abs($w - $baseH) < 0.0001 && abs($h - $baseW) < 0.0001)));
                                if (!$validOrientation || $group < 1 || $group > count($cuts) || $sheet < 0 || !is_finite($x) || !is_finite($y) || !is_finite($w) || !is_finite($h) || $w <= 0 || $h <= 0 || $x < $margin - 0.0001 || $y < $margin - 0.0001 || $x + $w > $stockW - $margin + 0.0001 || $y + $h > $stockH - $margin + 0.0001) {
                                    throw ValidationException::withMessages(['materials' => 'Ada potongan manual yang berada di luar batas lembar master.']);
                                }
                                $areaByGroup[$group] = ($areaByGroup[$group] ?? 0) + ($w * $h);
                            }
                            for ($i = 0; $i < count($pieces); $i++) {
                                $a = $pieces[$i];
                                for ($j = $i + 1; $j < count($pieces); $j++) {
                                    $b = $pieces[$j];
                                    if ((int)($a['sheet'] ?? -1) !== (int)($b['sheet'] ?? -2)) continue;
                                    $ax=(float)$a['x']; $ay=(float)$a['y']; $aw=(float)$a['w']; $ah=(float)$a['h'];
                                    $bx=(float)$b['x']; $by=(float)$b['y']; $bw=(float)$b['w']; $bh=(float)$b['h'];
                                    if ($ax < $bx+$bw-0.0001 && $ax+$aw > $bx+0.0001 && $ay < $by+$bh-0.0001 && $ay+$ah > $by+0.0001) {
                                        throw ValidationException::withMessages(['materials' => 'Susunan manual ditolak karena ada potongan yang saling bertumpuk.']);
                                    }
                                    if ($kerf > 0 && $ax < $bx+$bw+$kerf-0.0001 && $ax+$aw+$kerf > $bx+0.0001 && $ay < $by+$bh+$kerf-0.0001 && $ay+$ah+$kerf > $by+0.0001) {
                                        throw ValidationException::withMessages(['materials' => 'Jarak antar-potongan lebih kecil daripada kerf yang ditentukan.']);
                                    }
                                }
                            }
                            foreach ($cuts as $idx => $cut) {
                                $expectedArea = $cut['length_cm'] * $cut['width_cm'] * $cut['quantity'];
                                $actualArea = $areaByGroup[$idx + 1] ?? 0;
                                if (abs($expectedArea - $actualArea) > max(0.05, $expectedArea * 0.00001)) {
                                    throw ValidationException::withMessages(['materials' => 'Luas susunan manual tidak cocok dengan kebutuhan ukuran baris '.($idx + 1).'.']);
                                }
                            }
                            $layout['pieces'] = array_values($pieces);
                            $entry['cut_layout'] = $layout;
                        }
                        $entry['quantity'] = (float) $totalQty;
                        $attributes['length_cm'] = (string) $cuts[0]['length_cm'];
                        $attributes['width_cm'] = (string) $cuts[0]['width_cm'];
                    }
                    $custom = [];
                    $seen = [];
                    foreach (($row['custom_attributes'] ?? []) as $attr) {
                        $attrName = trim((string) ($attr['name'] ?? ''));
                        $attrValue = trim((string) ($attr['value'] ?? ''));
                        $lower = mb_strtolower($attrName);
                        if ($attrName === '' || $attrValue === '' || isset($seen[$lower])) {
                            throw ValidationException::withMessages(['materials' => 'Atribut tambahan harus bernama, memiliki nilai, dan tidak duplikat.']);
                        }
                        $seen[$lower] = true;
                        $custom[] = ['name' => $attrName, 'value' => $attrValue];
                    }
                    $entry['custom_attributes'] = $custom;
                    $entry['category'] = $category;
                    $entry['attributes'] = $attributes;
                    if (!empty($row['material_variant_id'])) {
                        $entry['material_variant_id'] = (int) $row['material_variant_id'];
                        $entry['mm05_snapshot'] = $row['mm05_snapshot'];
                        app(\App\Services\MmSjrThicknessGuard::class)->assertCompatible($entry);
                    }
                }
                if ($withPricing) {
                    $price = $row['unit_price'] ?? null;
                    $entry['unit_price'] = ($price === null || $price === '') ? null : (float) $price;
                }
                $out[] = $entry;
            }

            return $out;
        };
        DB::transaction(function()use($request, $jobNumber, $itemId, $input, $normalize) {
            $handoff = DB::table('sjr_multi_item_handoffs as h')
                ->join('sjr_jobs as j', 'j.id', '=', 'h.sjr_job_id')
                ->where('j.job_number', $jobNumber)
                ->select('h.id', 'j.id as job_id')->lockForUpdate()->first();
            abort_unless($handoff, 404);
            abort_unless(Schema::hasTable('sjr_multi_item_technical_finalizations'), 503, 'Migrasi finalisasi SJR V5.8.2 belum dijalankan.');
            if (DB::table('sjr_multi_item_technical_finalizations')->where('handoff_id', $handoff->id)->exists()) {
                throw ValidationException::withMessages(['materials' => 'Persiapan teknis READY_FOR_HPP sudah terkunci, perubahan draft ditolak.']);
            }
            $item = DB::table('sjr_job_items')->where('id', $itemId)->where('sjr_job_id', $handoff->job_id)->first();
            abort_unless($item, 404);
            $review = DB::table('sjr_multi_item_brief_reviews')->where('handoff_id', $handoff->id)->first();
            abort_unless($review && $review->decision === 'CLEAR', 409, 'Brief belum dinyatakan cukup jelas.');
            $existing = DB::table('sjr_multi_item_technical_drafts')->where('handoff_id', $handoff->id)->where('sjr_job_item_id', $itemId)->first();
            // SJR_ATTACHMENT_SAFE_V1: only retain metadata from this exact saved row.
            $previousRows = $existing ? (json_decode($existing->materials_json, true) ?: []) : [];
            foreach (($input['materials'] ?? []) as $idx => $materialRow) {
                $prior = $previousRows[$idx] ?? [];
                $sameRow = ($prior['source_type'] ?? '') === 'new_material_request'
                    && ($materialRow['source_type'] ?? '') === 'new_material_request'
                    && trim((string)($prior['name'] ?? '')) === trim((string)($materialRow['name'] ?? ''));
                $trusted = $sameRow ? ($prior['attachments'] ?? []) : [];
                $byPath = [];
                foreach ($trusted as $att) {
                    if (is_array($att) && !empty($att['path'])) $byPath[$att['path']] = $att;
                }
                $requested = json_decode((string)($materialRow['existing_attachments'] ?? ''), true);
                if (!is_array($requested)) $requested = [];
                $kept = [];
                foreach ($requested as $att) {
                    $path = is_array($att) ? ($att['path'] ?? '') : '';
                    if (!is_string($path) || !isset($byPath[$path])) {
                        throw ValidationException::withMessages(['materials' => 'Daftar lampiran tidak sesuai dengan draft tersimpan. Muat ulang halaman.']);
                    }
                    if (!isset($kept[$path])) $kept[$path] = $byPath[$path];
                }
                $input['materials'][$idx]['_attachments'] = array_values($kept);
            }
            $materialsInput = $input['materials'] ?? [];
            $materials = $normalize(app(\App\Services\MmSjrMaterialCatalog::class)->apply($materialsInput));
            $processes = $normalize($input['processes'] ?? [], true);
            // Validate all material rows before writing any files.
            foreach ($materialsInput as $idx => $materialRow) {
                $uploaded = $request->file("materials.$idx.attachments", []);
                if (!is_array($uploaded) || !$uploaded) continue;
                if (($materialRow['source_type'] ?? '') !== 'new_material_request') {
                    throw ValidationException::withMessages(['materials' => 'Lampiran hanya untuk pengajuan material baru.']);
                }
                foreach ($uploaded as $file) {
                    if (!$file || !$file->isValid()) continue;
                    $path = $file->store("sjr/material-requests/drafts/{$jobNumber}/{$itemId}", 'local');
                    $metadata = ['path' => $path, 'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(), 'size' => $file->getSize()];
                    // Match material rows by their original input index, not array position after normalization.
                    $rowPosition = 0;
                    foreach ($materialsInput as $originalIndex => $candidate) {
                        $name = trim((string)($candidate['name'] ?? ''));
                        $spec = trim((string)($candidate['spec'] ?? ''));
                        $unit = trim((string)($candidate['unit'] ?? ''));
                        $qty = $candidate['quantity'] ?? null;
                        if ($name === '' && $spec === '' && $unit === '' && ($qty === null || $qty === '')) continue;
                        if ((string)$originalIndex === (string)$idx) break;
                        $rowPosition++;
                    }
                    $materials[$rowPosition]['attachments'][] = $metadata;
                }
            }
            $now = now();
            DB::table('sjr_multi_item_technical_drafts')->updateOrInsert(
                ['handoff_id' => $handoff->id, 'sjr_job_item_id' => $itemId],
                ['materials_json' => json_encode($materials, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'processes_json' => json_encode($processes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'instructions' => trim((string) ($input['instructions'] ?? '')),
                    'leadtime_days' => array_key_exists('leadtime_days', $input) ? (($input['leadtime_days'] ?? '') === '' ? null : (int) $input['leadtime_days']) : $existing?->leadtime_days,
                    'leadtime_note' => array_key_exists('leadtime_note', $input) ? trim((string) ($input['leadtime_note'] ?? '')) : $existing?->leadtime_note,
                    'saved_by' => $request->user()->id, 'saved_at' => $now, 'updated_at' => $now,
                    'created_at' => $existing?->created_at ?? $now]
            );
        }, 3);

        return redirect()->route('sjr.production.multi.show', $jobNumber)
            ->with('sjr_technical_success', 'Draft teknis item berhasil disimpan. Buka kembali item untuk memeriksa data.')
            ->with('sjr_technical_item', $itemId);
    }
    public function downloadMaterialAttachment(Request $request, string $jobNumber, int $itemId, int $materialIndex, int $attachmentIndex)
    {
        abort_unless(config('database.default') === 'pgsql' && (DB::connection()->getDatabaseName() === 'enterprise_bpm_browser' || (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test')), 403);
        abort_unless($request->user() && $request->user()->roles()->whereIn('code', ['PRODUCTION','PRODUCTION_ADMIN','PRODUCTION_MANAGER','ADMIN_PRODUKSI','MANAGER_PRODUKSI','ASST_MANAGER_PRODUKSI','ASSISTANT_MANAGER_PRODUKSI','PRODUKSI'])->exists(), 403);
        $draft = DB::table('sjr_multi_item_technical_drafts as d')
            ->join('sjr_multi_item_handoffs as h', 'h.id', '=', 'd.handoff_id')
            ->join('sjr_jobs as j', 'j.id', '=', 'h.sjr_job_id')
            ->join('sjr_job_items as i', 'i.id', '=', 'd.sjr_job_item_id')
            ->where('j.job_number', $jobNumber)->whereColumn('i.sjr_job_id', 'j.id')
            ->where('d.sjr_job_item_id', $itemId)->select('d.materials_json')->first();
        abort_unless($draft, 404);
        $rows = json_decode($draft->materials_json, true) ?: [];
        $attachment = $rows[$materialIndex]['attachments'][$attachmentIndex] ?? null;
        abort_unless(is_array($attachment) && !empty($attachment['path']), 404);
        $path = $attachment['path'];
        $prefix = "sjr/material-requests/drafts/{$jobNumber}/{$itemId}/";
        abort_unless(str_starts_with($path, $prefix) && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->download($path, basename($attachment['original_name'] ?? 'lampiran'), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

}
