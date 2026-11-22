<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** SJR V5.5 Stage 3: Marketing decisions on individual Designer submissions. No Production handoff. */
class SjrMarketingItemReviewController extends Controller
{
    private function authorizeOwner(Request $request, int $item): void
    {
        abort_unless(config('database.default') === 'pgsql' && DB::connection()->getDatabaseName() === 'enterprise_bpm_browser', 403);
        abort_unless($request->user() && $request->user()->roles()->where('code', 'MARKETING')->exists(), 403);
        abort_unless(Schema::hasTable('sjr_item_design_reviews'), 503, 'Migrasi SJR V5.5 Tahap 3 belum dijalankan.');
        abort_unless(DB::table('sjr_job_items as i')->join('sjr_jobs as j', 'j.id', '=', 'i.sjr_job_id')
            ->where('i.id', $item)->where('i.requires_designer', true)
            ->where('j.marketing_user_id', $request->user()->id)->where('j.status', 'MULTI_ITEM_PENDING')->exists(), 404);
    }

    public function decide(Request $request, int $item)
    {
        $this->authorizeOwner($request, $item);
        $data = $request->validate([
            'submission_id' => 'required|integer|min:1',
            'decision' => 'required|in:approve,revision',
            'note' => 'nullable|string|max:3000',
        ]);
        $note = trim((string) ($data['note'] ?? ''));
        if ($data['decision'] === 'revision' && $note === '') {
            throw ValidationException::withMessages(['note' => 'Catatan revisi wajib diisi.']);
        }
        $decision = $data['decision'] === 'approve' ? 'APPROVED' : 'REVISION_REQUESTED';
        $result = DB::transaction(function()use($request, $item, $data, $note, $decision) {
            $current = DB::table('sjr_job_items')->where('id', $item)->lockForUpdate()->first();
            abort_unless($current, 404);
            if ($current->design_state !== 'IN_REVIEW') {
                throw ValidationException::withMessages(['status' => 'Item tidak sedang menunggu keputusan Marketing. Muat ulang halaman.']);
            }
            $latest = DB::table('sjr_item_design_submissions')->where('sjr_job_item_id', $item)
                ->where('state', 'IN_REVIEW')->orderByDesc('version')->first();
            if (!$latest || (int) $latest->id !== (int) $data['submission_id']) {
                throw ValidationException::withMessages(['submission_id' => 'Versi desain sudah berubah. Muat ulang untuk melihat versi terbaru.']);
            }
            if (DB::table('sjr_item_design_reviews')->where('submission_id', $latest->id)->exists()) {
                throw ValidationException::withMessages(['submission_id' => 'Versi ini sudah pernah diputuskan.']);
            }
            $now = now();
            DB::table('sjr_item_design_reviews')->insert([
                'sjr_job_item_id' => $item, 'submission_id' => $latest->id, 'decision' => $decision,
                'reviewer_user_id' => $request->user()->id, 'note' => $note !== '' ? $note : null,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('sjr_job_items')->where('id', $item)
                ->update(['design_state' => $decision, 'updated_at' => $now]);
            $unfinished = DB::table('sjr_job_items')->where('sjr_job_id', $current->sjr_job_id)
                ->where('requires_designer', true)->where('design_state', '!=', 'APPROVED')->count();

            return ['item_id' => $item, 'design_state' => $decision, 'version' => $latest->version,
                'all_required_items_approved' => $unfinished === 0, 'handoff_enabled' => false];
        }, 3);

        return response()->json(['message' => $decision === 'APPROVED' ? 'Desain item disetujui Marketing.' : 'Catatan revisi dikirim ke Designer.',
            'result' => $result])->header('Cache-Control', 'private, no-store');
    }
}
