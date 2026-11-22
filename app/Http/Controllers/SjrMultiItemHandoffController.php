<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** SJR V5.6: isolated, atomic full-project handoff. No PROD-02..09 mutations. */
class SjrMultiItemHandoffController extends Controller
{
    private const PRODUCTION_ROLES = ['PRODUCTION', 'PRODUCTION_ADMIN', 'PRODUCTION_MANAGER', 'ADMIN_PRODUKSI', 'MANAGER_PRODUKSI', 'ASST_MANAGER_PRODUKSI', 'ASSISTANT_MANAGER_PRODUKSI', 'PRODUKSI'];

    private function environment(): void
    {
        abort_unless(config('database.default') === 'pgsql' && (DB::connection()->getDatabaseName() === 'enterprise_bpm_browser' || (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test')), 403, 'SJR V5.6 hanya dapat digunakan pada database browser pengujian.');
        abort_unless(Schema::hasTable('sjr_multi_item_handoffs') && Schema::hasTable('sjr_job_items') && Schema::hasTable('sjr_item_design_submissions'), 503, 'Migrasi SJR V5.6 belum dijalankan.');
    }

    private function marketing(Request $request): void
    {
        $this->environment();
        abort_unless($request->user() && $request->user()->roles()->where('code', 'MARKETING')->exists(), 403);
    }

    private function production(Request $request): void
    {
        $this->environment();
        abort_unless($request->user() && $request->user()->roles()->whereIn('code', self::PRODUCTION_ROLES)->exists(), 403);
    }

    public function send(Request $request, int $job)
    {
        $this->marketing($request);
        $result = DB::transaction(function()use($request, $job) {
            $record = DB::table('sjr_jobs')->where('id', $job)->lockForUpdate()->first();
            abort_unless($record && (int) $record->marketing_user_id === (int) $request->user()->id, 404);
            if (DB::table('sjr_multi_item_handoffs')->where('sjr_job_id', $record->id)->exists()) {
                throw ValidationException::withMessages(['job' => 'Project ini sudah pernah dikirim ke Produksi.']);
            }
            if ($record->status !== 'MULTI_ITEM_PENDING') {
                throw ValidationException::withMessages(['job' => 'Status Project tidak dapat dikirim. Muat ulang halaman.']);
            }
            $items = DB::table('sjr_job_items')->where('sjr_job_id', $record->id)->orderBy('item_index')->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Project tidak memiliki item.']);
            }
            $payload = [];
            foreach ($items as $item) {
                $submission = null;
                if ($item->requires_designer) {
                    if ($item->design_state !== 'APPROVED') {
                        throw ValidationException::withMessages(['items' => 'Semua desain item harus disetujui Marketing terlebih dahulu.']);
                    }
                    $submission = DB::table('sjr_item_design_submissions')
                        ->where('sjr_job_item_id', $item->id)->where('state', 'IN_REVIEW')
                        ->orderByDesc('version')->first();
                    // Review evidence is mandatory, and ties the latest submitted version to the approval.
                    if (!$submission || !DB::table('sjr_item_design_reviews')->where('submission_id', $submission->id)->where('decision', 'APPROVED')->exists()) {
                        throw ValidationException::withMessages(['items' => 'Persetujuan desain item tidak memiliki bukti review versi terbaru.']);
                    }
                }
                $payload[] = ['item_id' => $item->id, 'index' => $item->item_index, 'name' => $item->item_name,
                    'dimensions' => $item->dimensions, 'dimension_unit' => $item->dimension_unit, 'quantity' => $item->quantity,
                    'requires_designer' => (bool) $item->requires_designer, 'approved_submission_id' => $submission?->id,
                    'approved_version' => $submission?->version];
            }
            $now = now();
            DB::table('sjr_multi_item_handoffs')->insert([
                'sjr_job_id' => $record->id, 'sent_by' => $request->user()->id,
                'item_snapshot_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'sent_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('sjr_jobs')->where('id', $record->id)->update(['status' => 'MULTI_ITEM_SENT', 'updated_at' => $now]);

            return ['job_number' => $record->job_number, 'items' => count($payload), 'status' => 'MULTI_ITEM_SENT'];
        }, 3);

        return response()->json(['message' => 'Seluruh Project berhasil dikirim ke Production Inbox Multi-Item.', 'result' => $result])->header('Cache-Control', 'private, no-store');
    }

    public function download(Request $request, string $jobNumber, int $file)
    {
        $this->production($request);
        $record = DB::table('sjr_multi_item_handoffs as h')->join('sjr_jobs as j', 'j.id', '=', 'h.sjr_job_id')
            ->where('j.job_number', $jobNumber)->select('j.id')->first();
        abort_unless($record, 404);
        $attachment = DB::table('sjr_attachments')->where('sjr_job_id', $record->id)->where('id', $file)->first();
        abort_unless($attachment && Storage::disk('local')->exists($attachment->storage_path), 404);

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function preview(Request $request, string $jobNumber, int $preview)
    {
        $this->production($request);
        $found = DB::table('sjr_item_previews as p')->join('sjr_job_items as i', 'i.id', '=', 'p.sjr_job_item_id')
            ->join('sjr_jobs as j', 'j.id', '=', 'i.sjr_job_id')
            ->join('sjr_multi_item_handoffs as h', 'h.sjr_job_id', '=', 'j.id')
            ->where('j.job_number', $jobNumber)->where('p.id', $preview)
            ->select('p.storage_path', 'p.mime_type')->first();
        abort_unless($found && Storage::disk('local')->exists($found->storage_path), 404);

        return response()->file(Storage::disk('local')->path($found->storage_path),
            ['Content-Type' => $found->mime_type, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function designFile(Request $request, string $jobNumber, int $file)
    {
        $this->production($request);
        $record = DB::table('sjr_multi_item_handoffs as h')->join('sjr_jobs as j', 'j.id', '=', 'h.sjr_job_id')
            ->where('j.job_number', $jobNumber)->select('h.item_snapshot_json')->first();
        abort_unless($record, 404);
        $approvedIds = collect(json_decode($record->item_snapshot_json, true, 512, JSON_THROW_ON_ERROR))
            ->pluck('approved_submission_id')->filter()->values()->all();
        $attachment = DB::table('sjr_item_design_files')->whereIn('submission_id', $approvedIds)->where('id', $file)->first();
        abort_unless($attachment && Storage::disk('local')->exists($attachment->storage_path), 404);

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function inbox(Request $request)
    {
        $this->production($request);
        $jobs = DB::table('sjr_multi_item_handoffs as h')->join('sjr_jobs as j', 'j.id', '=', 'h.sjr_job_id')
            ->select('j.job_number', 'j.job_title', 'j.client_name', 'j.target_date', 'j.id', 'h.sent_at', 'h.id as handoff_id')
            ->orderByDesc('h.sent_at')->get();
        $briefReviews = DB::table('sjr_multi_item_brief_reviews')->get()->keyBy('handoff_id');

        return view('sjr.multi-item-production-inbox', compact('jobs', 'briefReviews'));
    }

    public function show(Request $request, string $jobNumber)
    {
        $this->production($request);
        $record = DB::table('sjr_multi_item_handoffs as h')->join('sjr_jobs as j', 'j.id', '=', 'h.sjr_job_id')
            ->where('j.job_number', $jobNumber)->select('j.*', 'h.item_snapshot_json', 'h.sent_at', 'h.id as handoff_id')->first();
        abort_unless($record, 404);
        $items = json_decode($record->item_snapshot_json, true, 512, JSON_THROW_ON_ERROR);
        $approvedIds = collect($items)->pluck('approved_submission_id')->filter()->values()->all();
        $designFiles = DB::table('sjr_item_design_files')->whereIn('submission_id', $approvedIds)->orderBy('id')->get()->groupBy('submission_id');
        $attachments = DB::table('sjr_attachments')->where('sjr_job_id', $record->id)->orderBy('id')->get();
        $previews = DB::table('sjr_item_previews as p')->join('sjr_job_items as i', 'i.id', '=', 'p.sjr_job_item_id')
            ->where('i.sjr_job_id', $record->id)->select('p.id', 'p.sjr_job_item_id', 'p.original_name')->get()->groupBy('sjr_job_item_id');
        $briefReview = DB::table('sjr_multi_item_brief_reviews')->where('handoff_id', $record->handoff_id)->first();
        $briefHistory = DB::table('sjr_multi_item_brief_review_events as e')
            ->leftJoin('users as u', 'u.id', '=', 'e.reviewed_by')
            ->where('e.handoff_id', $record->handoff_id)
            ->orderByDesc('e.id')->select('e.*', 'u.name as reviewer_name')->get();
        $canBriefReview = $request->user()->roles()->whereIn('code', ['ASST_MANAGER_PRODUKSI', 'ASSISTANT_MANAGER_PRODUKSI'])->exists();
        abort_unless(Schema::hasTable('sjr_multi_item_technical_drafts'), 503, 'Migrasi SJR V5.7.2 belum dijalankan.');
        $technicalDrafts = DB::table('sjr_multi_item_technical_drafts')->where('handoff_id', $record->handoff_id)->get()->keyBy('sjr_job_item_id');
        abort_unless(Schema::hasTable('sjr_multi_item_technical_finalizations'), 503, 'Migrasi V5.8.2 belum dijalankan.');
        $technicalFinalization = DB::table('sjr_multi_item_technical_finalizations')->where('handoff_id', $record->handoff_id)->first();
        $technicalReady = $briefReview && $briefReview->decision === 'CLEAR';
        $canEditTechnical = $technicalReady && $canBriefReview && !$technicalFinalization;

        $mm05Options = app(\App\Services\MmSjrMaterialCatalog::class)->options();
        return view($request->routeIs('sjr.production.multi.tabler-preview') ? 'sjr.multi-item-production-detail-tabler' : 'sjr.multi-item-production-detail', compact('record', 'items', 'attachments', 'previews', 'designFiles', 'briefReview', 'briefHistory', 'canBriefReview', 'technicalDrafts', 'technicalReady', 'canEditTechnical', 'technicalFinalization', 'mm05Options'));
    }
}
