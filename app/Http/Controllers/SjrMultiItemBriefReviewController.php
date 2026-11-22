<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** SJR V5.7.1: Project multi-item brief review only; no PROD-02..09 write. */
class SjrMultiItemBriefReviewController extends Controller
{
    public function store(Request $request, string $jobNumber)
    {
        abort_unless(config('database.default') === 'pgsql' && DB::connection()->getDatabaseName() === 'enterprise_bpm_browser', 403, 'Hanya database pengujian SJR.');
        abort_unless(Schema::hasTable('sjr_multi_item_brief_reviews') && Schema::hasTable('sjr_multi_item_brief_review_events'), 503, 'Migrasi V5.7.1 belum dijalankan.');
        abort_unless($request->user() && $request->user()->roles()->whereIn('code', ['ASST_MANAGER_PRODUKSI', 'ASSISTANT_MANAGER_PRODUKSI'])->exists(), 403, 'Hanya Asst. Manager Produksi dapat memeriksa brief.');
        $data = $request->validate([
            'decision' => ['required', 'in:CLEAR,CLARIFICATION'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);
        $note = trim((string) ($data['note'] ?? ''));
        if ($data['decision'] === 'CLARIFICATION' && $note === '') {
            throw ValidationException::withMessages(['note' => 'Catatan wajib diisi jika meminta klarifikasi.']);
        }
        DB::transaction(function()use($request, $jobNumber, $data, $note) {
            $handoff = DB::table('sjr_multi_item_handoffs as h')->join('sjr_jobs as j', 'j.id', '=', 'h.sjr_job_id')
                ->where('j.job_number', $jobNumber)->select('h.id')->lockForUpdate()->first();
            abort_unless($handoff, 404);
            $current = DB::table('sjr_multi_item_brief_reviews')->where('handoff_id', $handoff->id)->first();
            if ($current && $current->decision === 'CLEAR') {
                throw ValidationException::withMessages(['decision' => 'Brief sudah dinyatakan cukup jelas dan tidak dapat diubah di tahap ini.']);
            }
            if ($current && $current->decision === 'CLARIFICATION' && $data['decision'] === 'CLARIFICATION') {
                throw ValidationException::withMessages(['decision' => 'Klarifikasi sudah dicatat. Tidak perlu menyimpan keputusan yang sama kembali.']);
            }
            $now = now();
            DB::table('sjr_multi_item_brief_reviews')->updateOrInsert(['handoff_id' => $handoff->id], [
                'decision' => $data['decision'], 'note' => $note, 'reviewed_by' => $request->user()->id,
                'reviewed_at' => $now, 'updated_at' => $now, 'created_at' => $current?->created_at ?? $now,
            ]);
            DB::table('sjr_multi_item_brief_review_events')->insert([
                'handoff_id' => $handoff->id, 'decision' => $data['decision'], 'note' => $note,
                'reviewed_by' => $request->user()->id, 'reviewed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }, 3);

        return redirect()->route('sjr.production.multi.show', $jobNumber)->with('sjr_review_success', 'Keputusan pemeriksaan brief berhasil disimpan.');
    }
}
