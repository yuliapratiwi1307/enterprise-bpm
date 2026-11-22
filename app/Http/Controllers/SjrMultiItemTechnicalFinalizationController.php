<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** V5.8.2: atomic, project-wide technical finalization, WITHOUT launching HPP. */
class SjrMultiItemTechnicalFinalizationController extends Controller
{
    public function store(Request $request, string $jobNumber)
    {
        abort_unless(config('database.default') === 'pgsql' && (DB::connection()->getDatabaseName() === 'enterprise_bpm_browser' || (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test')), 403, 'Hanya database SJR browser.');
        abort_unless(Schema::hasTable('sjr_multi_item_technical_finalizations'), 503, 'Jalankan migration V5.8.2 yang terisolasi.');
        abort_unless($request->user() && $request->user()->roles()->whereIn('code', ['ASST_MANAGER_PRODUKSI', 'ASSISTANT_MANAGER_PRODUKSI'])->exists(), 403);
        $request->validate(['confirm' => ['required', 'accepted']]);

        DB::transaction(function()use($request, $jobNumber) {
            $job = DB::table('sjr_jobs')->where('job_number', $jobNumber)->first();
            abort_unless($job, 404);
            // Same handoff row lock as technical draft saves: serializes save and finalization.
            $handoff = DB::table('sjr_multi_item_handoffs')->where('sjr_job_id', $job->id)->lockForUpdate()->first();
            abort_unless($handoff, 404);
            if (DB::table('sjr_multi_item_technical_finalizations')->where('handoff_id', $handoff->id)->exists()) {
                throw ValidationException::withMessages(['finalize' => 'Persiapan teknis sudah difinalisasi dan terkunci.']);
            }
            $review = DB::table('sjr_multi_item_brief_reviews')->where('handoff_id', $handoff->id)->first();
            if (!$review || $review->decision !== 'CLEAR') {
                throw ValidationException::withMessages(['finalize' => 'Brief belum dinyatakan cukup jelas.']);
            }
            $snapshot = json_decode($handoff->item_snapshot_json, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($snapshot) || count($snapshot) < 1) {
                throw ValidationException::withMessages(['finalize' => 'Snapshot item proyek kosong.']);
            }
            $itemIds = array_map(fn($item) => $item['item_id'] ?? null, $snapshot);
            if (in_array(null, $itemIds, true) || count(array_unique($itemIds)) !== count($itemIds)) {
                throw ValidationException::withMessages(['finalize' => 'Snapshot item proyek tidak valid.']);
            }
            $actualIds = DB::table('sjr_job_items')->where('sjr_job_id', $job->id)->orderBy('id')->pluck('id')->map(fn($id) => (int) $id)->all();
            $expectedIds = array_map('intval', $itemIds);
            sort($actualIds);
            sort($expectedIds);
            if ($actualIds !== $expectedIds) {
                throw ValidationException::withMessages(['finalize' => 'Daftar item berubah sejak handoff. Finalisasi ditolak.']);
            }
            $drafts = DB::table('sjr_multi_item_technical_drafts')->where('handoff_id', $handoff->id)->orderBy('sjr_job_item_id')->lockForUpdate()->get()->keyBy('sjr_job_item_id');
            if ($drafts->count() !== count($snapshot)) {
                throw ValidationException::withMessages(['finalize' => 'Semua item wajib memiliki draft persiapan teknis tersimpan.']);
            }
            $captured = [];
            foreach ($snapshot as $item) {
                $id = (int) $item['item_id'];
                $draft = $drafts->get($id);
                if (!$draft) {
                    throw ValidationException::withMessages(['finalize' => 'Draft item belum lengkap.']);
                }
                $materials = json_decode($draft->materials_json, true, 512, JSON_THROW_ON_ERROR);
                $processes = json_decode($draft->processes_json, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($materials) || count($materials) < 1 || ! is_array($processes) || count($processes) < 1) {
                    throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': material dan proses/jasa wajib diisi.']);
                }
                foreach ($materials as $m) {
                    if (! is_array($m) || trim((string) ($m['name'] ?? '')) === '' || ! is_numeric($m['quantity'] ?? null) || (float) $m['quantity'] <= 0) {
                        throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': nama material dan quantity wajib valid.']);
                    }
                    if (($m['source_type'] ?? null) === 'new_material_request') {
                        if (trim((string) ($m['spec'] ?? '')) === '') {
                            throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': spesifikasi material baru wajib diisi.']);
                        }
                        continue;
                    }
                    if (trim((string) ($m['unit'] ?? '')) === '') {
                        throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': satuan material MM-05 wajib valid.']);
                    }
                    $category = $m['category'] ?? null;
                    if ($category === 'sheet' && isset($m['cut_settings'])) {
                        $settings = $m['cut_settings'];
                        if (! is_array($settings) || ! is_numeric($settings['kerf_cm'] ?? null) || (float) $settings['kerf_cm'] < 0 || (float) $settings['kerf_cm'] > 5 || ! is_numeric($settings['edge_margin_cm'] ?? null) || (float) $settings['edge_margin_cm'] < 0 || (float) $settings['edge_margin_cm'] > 50) {
                            throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': pengaturan kerf atau margin tidak valid.']);
                        }
                    }
                    $required = ['sheet' => ['thickness_mm', 'length_cm', 'width_cm'], 'profile' => ['profile_size', 'length_m'], 'pipe' => ['diameter', 'length_m'], 'cable' => ['cable_type', 'length_m'], 'liquid' => ['variant', 'volume_l'], 'hardware' => ['type'], 'other' => ['description']];
                    if ($category !== null && ! isset($required[$category])) {
                        throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': jenis material tidak dikenal.']);
                    }
                    if ($category !== null) {
                        $attributes = $m['attributes'] ?? [];
                        if (! is_array($attributes)) {
                            throw ValidationException::withMessages(['finalize' => 'Atribut material tidak valid.']);
                        }
                        foreach ($required[$category] as $key) {
                            if (trim((string) ($attributes[$key] ?? '')) === '') {
                                throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': spesifikasi material '.$key.' belum diisi.']);
                            }
                        }
                        $custom = $m['custom_attributes'] ?? [];
                        if (! is_array($custom) || count($custom) > 10) {
                            throw ValidationException::withMessages(['finalize' => 'Atribut tambahan material tidak valid.']);
                        }
                        $names = [];
                        foreach ($custom as $attribute) {
                            $name = mb_strtolower(trim((string) ($attribute['name'] ?? '')));
                            $value = trim((string) ($attribute['value'] ?? ''));
                            if ($name === '' || $value === '' || isset($names[$name])) {
                                throw ValidationException::withMessages(['finalize' => 'Atribut tambahan kosong atau duplikat.']);
                            }
                            $names[$name] = true;
                        }
                    } elseif (trim((string) ($m['spec'] ?? '')) === '') {
                        throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': spesifikasi material legacy tidak lengkap.']);
                    }
                }
                if (! is_numeric($draft->leadtime_days ?? null) || (int) $draft->leadtime_days < 1 || (int) $draft->leadtime_days > 3650 || (float) $draft->leadtime_days != (int) $draft->leadtime_days) {
                    throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': lead time wajib diisi antara 1 sampai 3650 hari.']);
                }
                foreach ($processes as $proc) {
                    if (! is_array($proc) || trim((string) ($proc['name'] ?? '')) === '' || trim((string) ($proc['unit'] ?? '')) === '' || ! is_numeric($proc['quantity'] ?? null) || (float) $proc['quantity'] <= 0 || ! is_numeric($proc['unit_price'] ?? null) || (float) $proc['unit_price'] < 0) {
                        throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': nama, satuan, jumlah, dan harga Proses/Jasa wajib valid.']);
                    }
                }
                if (trim((string) ($draft->instructions ?? '')) === '') {
                    throw ValidationException::withMessages(['finalize' => 'Item '.$item['index'].': Catatan belum diisi.']);
                }
                $captured[] = ['item_id' => $id, 'item_index' => $item['index'], 'draft_id' => $draft->id, 'materials' => $materials, 'processes' => $processes, 'notes' => $draft->instructions, 'leadtime_days' => (int) $draft->leadtime_days, 'leadtime_note' => $draft->leadtime_note, 'saved_at' => $draft->saved_at];
            }
            $now = now();
            DB::table('sjr_multi_item_technical_finalizations')->insert([
                'handoff_id' => $handoff->id, 'state' => 'READY_FOR_HPP', 'item_count' => count($captured),
                'technical_snapshot_json' => json_encode($captured, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'finalized_by' => $request->user()->id, 'finalized_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);

            // Permintaan material baru diterbitkan atomik setelah snapshot teknis terkunci.
            if (Schema::hasTable('sjr_material_requests')) {
                foreach ($captured as $capturedItem) {
                    foreach (($capturedItem['materials'] ?? []) as $materialIndex => $material) {
                        if (($material['source_type'] ?? null) !== 'new_material_request') continue;
                        $exists = DB::table('sjr_material_requests')->where('handoff_id', $handoff->id)->where('sjr_job_item_id', $capturedItem['item_id'])->where('material_index', $materialIndex)->exists();
                        if ($exists) continue;
                        $requestId = DB::table('sjr_material_requests')->insertGetId([
                            'handoff_id' => $handoff->id, 'sjr_job_id' => $job->id, 'sjr_job_item_id' => $capturedItem['item_id'],
                            'technical_draft_id' => $capturedItem['draft_id'], 'material_index' => $materialIndex, 'material_name' => $material['name'],
                            'material_payload_json' => json_encode($material, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'status' => 'SUBMITTED',
                            'submitted_by' => $request->user()->id, 'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                        DB::table('sjr_material_requests')->where('id', $requestId)->update(['request_no' => 'MR-SJR-'.$now->format('Ymd').'-'.str_pad((string)$requestId, 6, '0', STR_PAD_LEFT)]);
                    }
                }
            }
        }, 3);

        return redirect()->route('sjr.production.multi.show', $jobNumber)->with('sjr_technical_success', 'Persiapan teknis seluruh item berhasil difinalisasi dan dikunci. Status READY_FOR_HPP; HPP belum dijalankan.');
    }
}
