<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** SJR V5.5 Stage 1: Designer can read ONLY item-level design requests, without mutation. */
class SjrDesignerItemInboxController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless(config('database.default') === 'pgsql' && DB::connection()->getDatabaseName() === 'enterprise_bpm_browser', 403);
        abort_unless($request->user() && $request->user()->roles()->where('code', 'DESIGNER')->exists(), 403);
        abort_unless(Schema::hasTable('sjr_jobs') && Schema::hasTable('sjr_job_items') && Schema::hasTable('sjr_item_previews'), 503);
    }

    private function eligibleQuery()
    {
        return DB::table('sjr_job_items as i')
            ->join('sjr_jobs as j', 'j.id', '=', 'i.sjr_job_id')
            ->where('j.status', 'MULTI_ITEM_PENDING')
            ->where('i.requires_designer', true);
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $items = $this->eligibleQuery()->orderByDesc('j.id')->orderBy('i.item_index')
            ->limit(200)->get([
                'i.id as item_id', 'i.sjr_job_id as job_id', 'i.item_index', 'i.item_name',
                'i.dimensions', 'i.dimension_unit', 'i.quantity', 'i.designer_brief',
                'i.designer_deadline', 'i.design_state', 'j.job_number',
                'j.job_title', 'j.client_name', 'j.target_date', 'j.created_at',
            ]);
        $ids = $items->pluck('item_id')->all();
        $previews = $ids ? DB::table('sjr_item_previews')
            ->whereIn('sjr_job_item_id', $ids)->orderBy('sort_order')
            ->get(['id', 'sjr_job_item_id', 'original_name', 'byte_size']) : collect();
        $jobIds = $items->pluck('job_id')->unique()->all();
        $documents = $jobIds ? DB::table('sjr_attachments')->whereIn('sjr_job_id', $jobIds)
            ->orderBy('id')->get(['id', 'sjr_job_id', 'original_name', 'byte_size', 'attachment_note']) : collect();
        $result = $items->map(function ($item) use ($previews, $documents) {
            $row = (array) $item;
            $row['previews'] = $previews->where('sjr_job_item_id', $item->item_id)
                ->values()->map(fn ($p) => ['id' => $p->id, 'name' => $p->original_name, 'size' => $p->byte_size])->all();
            $row['attachments'] = $documents->where('sjr_job_id', $item->job_id)->values()
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->original_name, 'size' => $d->byte_size, 'note' => $d->attachment_note])->all();

            return $row;
        });

        return response()->json(['items' => $result, 'stage' => 'READ_ONLY', 'handoff_enabled' => false])
            ->header('Cache-Control', 'private, no-store');
    }

    public function preview(Request $request, int $item, int $preview)
    {
        $this->guard($request);
        $file = $this->eligibleQuery()
            ->join('sjr_item_previews as p', 'p.sjr_job_item_id', '=', 'i.id')
            ->where('i.id', $item)->where('p.id', $preview)
            ->select('p.storage_path', 'p.mime_type')->first();
        abort_unless($file && Storage::disk('local')->exists($file->storage_path), 404);

        return response()->file(Storage::disk('local')->path($file->storage_path), [
            'Content-Type' => $file->mime_type, 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Authorized Designer can download only Project documents belonging to an eligible design item. */
    public function projectDocument(Request $request, int $item, int $document)
    {
        $this->guard($request);
        $file = $this->eligibleQuery()
            ->join('sjr_attachments as a', 'a.sjr_job_id', '=', 'j.id')
            ->where('i.id', $item)->where('a.id', $document)
            ->select('a.storage_path', 'a.original_name', 'a.mime_type')->first();
        abort_unless($file && Storage::disk('local')->exists($file->storage_path), 404);

        return Storage::disk('local')->download($file->storage_path, $file->original_name, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
