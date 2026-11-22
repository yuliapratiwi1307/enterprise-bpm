<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** SJR V5.5 Stage 2: isolated item design drafts and submissions. No Production handoff. */
class SjrDesignerItemResultController extends Controller
{
    private function authorizeAccess(
        Request $request,
        int $itemId,
        bool $designerOnly = false,
        bool $allowHandedOff = false,
    ): array {
        abort_unless(config('database.default') === 'pgsql' && DB::connection()->getDatabaseName() === 'enterprise_bpm_browser', 403);
        abort_unless($request->user(), 403);
        abort_unless(Schema::hasTable('sjr_item_design_submissions') && Schema::hasTable('sjr_item_design_files'), 503, 'Jalankan migrasi SJR V5.5 Tahap 2 terlebih dahulu.');
        $isDesigner = $request->user()->roles()->where('code', 'DESIGNER')->exists();
        $isMarketing = $request->user()->roles()->where('code', 'MARKETING')->exists();
        abort_unless($isDesigner || (!$designerOnly && $isMarketing), 403);
        $item = DB::table('sjr_job_items as i')->join('sjr_jobs as j', 'j.id', '=', 'i.sjr_job_id')
            ->where('i.id', $itemId)
            ->when(!$allowHandedOff, function($query) {
                $query->where('j.status', 'MULTI_ITEM_PENDING');
            })
            ->when($allowHandedOff, function($query) {
                $query->whereIn('j.status', ['MULTI_ITEM_PENDING', 'MULTI_ITEM_SENT']);
            })
            ->where('i.requires_designer', true)
            ->select('i.id', 'i.sjr_job_id', 'i.design_state', 'j.job_number', 'j.marketing_user_id')->first();
        abort_unless($item, 404);
        if (!$isDesigner) {
            abort_unless((int) $item->marketing_user_id === (int) $request->user()->id, 403);
        }

        return [$item, $isDesigner];
    }

    private function history(int $itemId, bool $allowDraft): array
    {
        $versions = DB::table('sjr_item_design_submissions')->where('sjr_job_item_id', $itemId)
            ->when(!$allowDraft, fn($q) => $q->where('state', 'IN_REVIEW'))
            ->orderByDesc('version')->get(['id', 'version', 'state', 'note', 'created_at', 'updated_at', 'submitted_at']);
        $files = $versions->isEmpty() ? collect() : DB::table('sjr_item_design_files')
            ->whereIn('submission_id', $versions->pluck('id')->all())->orderBy('id')
            ->get(['id', 'submission_id', 'original_name', 'byte_size', 'mime_type', 'created_at']);

        return $versions->map(function($v)use($files) {
            return ['id' => $v->id, 'version' => $v->version, 'state' => $v->state, 'note' => $v->note,
                'created_at' => $v->created_at, 'submitted_at' => $v->submitted_at,
                'files' => $files->where('submission_id', $v->id)->values()->map(fn($f) => [
                    'id' => $f->id, 'name' => $f->original_name, 'size' => $f->byte_size, 'mime' => $f->mime_type, 'created_at' => $f->created_at,
                ])->all()];
        })->all();
    }

    private function reviews(int $item): array
    {
        if (! Schema::hasTable('sjr_item_design_reviews')) {
            return [];
        }

        return DB::table('sjr_item_design_reviews as r')
            ->join('sjr_item_design_submissions as s', 's.id', '=', 'r.submission_id')
            ->where('r.sjr_job_item_id', $item)->orderBy('s.version')
            ->get(['r.submission_id', 's.version', 'r.decision', 'r.note', 'r.created_at'])
            ->map(fn($r) => (array) $r)->all();
    }

    public function index(Request $request, int $item)
    {
        [$record,$isDesigner] = $this->authorizeAccess($request, $item, false, true);

        return response()->json(['item_id' => $item, 'design_state' => $record->design_state,
            'versions' => $this->history($item, $isDesigner), 'reviews' => $this->reviews($item), 'handoff_enabled' => false])
            ->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request, int $item)
    {
        [$authorized] = $this->authorizeAccess($request, $item, true);
        $data = $request->validate([
            'action' => 'required|in:draft,submit',
            'note' => 'nullable|string|max:3000',
            'files' => 'nullable|array|max:10',
            'files.*' => 'required|file|max:204800|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,ai,psd,eps',
        ]);
        $newFiles = $request->file('files', []);
        $newFiles = is_array($newFiles) ? $newFiles : [];
        $stored = [];
        try {
            $result = DB::transaction(function()use($request, $item, $data, $newFiles, &$stored) {
                $record = DB::table('sjr_job_items as i')->join('sjr_jobs as j', 'j.id', '=', 'i.sjr_job_id')
                    ->where('i.id', $item)->where('j.status', 'MULTI_ITEM_PENDING')->where('i.requires_designer', true)
                    ->select('i.id', 'i.design_state')->first();
                abort_unless($record, 404);
                // Serialize all uploads and sends for this item.
                DB::table('sjr_job_items')->where('id', $item)->lockForUpdate()->first();
                $state = DB::table('sjr_job_items')->where('id', $item)->value('design_state');
                if (! in_array($state, ['PENDING', 'DRAFT', 'REVISION_REQUESTED'], true)) {
                    throw ValidationException::withMessages(['status' => 'Item sudah dikirim untuk review atau tidak dapat diubah.']);
                }
                $draft = DB::table('sjr_item_design_submissions')->where('sjr_job_item_id', $item)
                    ->where('state', 'DRAFT')->lockForUpdate()->first();
                $note = trim((string) ($data['note'] ?? ''));
                if (!$draft && !$newFiles && $note === '') {
                    throw ValidationException::withMessages(['files' => 'Isi catatan atau pilih berkas terlebih dahulu.']);
                }
                $version = $draft ? (int) $draft->version : 1 + (int) DB::table('sjr_item_design_submissions')->where('sjr_job_item_id', $item)->max('version');
                $existing = $draft ? DB::table('sjr_item_design_files')->where('submission_id', $draft->id)->count() : 0;
                if ($existing + count($newFiles) > 10) {
                    throw ValidationException::withMessages(['files' => 'Maksimal 10 berkas per versi desain.']);
                }
                if (($data['action'] === 'submit') && ($existing + count($newFiles) === 0)) {
                    throw ValidationException::withMessages(['files' => 'Unggah minimal satu berkas hasil desain sebelum dikirim.']);
                }
                $now = now();
                if ($draft) {
                    $id = $draft->id;
                    DB::table('sjr_item_design_submissions')->where('id', $id)
                        ->update(['note' => $note, 'updated_at' => $now]);
                } else {
                    $id = DB::table('sjr_item_design_submissions')->insertGetId([
                        'sjr_job_item_id' => $item, 'version' => $version, 'state' => 'DRAFT',
                        'designer_user_id' => $request->user()->id, 'note' => $note,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                foreach ($newFiles as $file) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $path = $file->storeAs('sjr/item-design-results/'.$item.'/'.$version, Str::uuid().'.'.$ext, 'local');
                    if (!$path) {
                        throw new \RuntimeException('Gagal menyimpan file hasil desain.');
                    }
                    $stored[] = $path;
                    DB::table('sjr_item_design_files')->insert([
                        'submission_id' => $id, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 250),
                        'storage_path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'byte_size' => $file->getSize(), 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                if ($data['action'] === 'submit') {
                    DB::table('sjr_item_design_submissions')->where('id', $id)
                        ->update(['state' => 'IN_REVIEW', 'submitted_at' => $now, 'updated_at' => $now]);
                    DB::table('sjr_job_items')->where('id', $item)
                        ->update(['design_state' => 'IN_REVIEW', 'updated_at' => $now]);
                } else {
                    DB::table('sjr_job_items')->where('id', $item)
                        ->update(['design_state' => 'DRAFT', 'updated_at' => $now]);
                }

                return ['item_id' => $item, 'version' => $version, 'state' => $data['action'] === 'submit' ? 'IN_REVIEW' : 'DRAFT'];
            }, 3);
        } catch (\Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return response()->json(['message' => $data['action'] === 'submit'
                ? 'Hasil desain item dikirim untuk review Marketing.' : 'Draft hasil desain item tersimpan.',
            'result' => $result, 'versions' => $this->history($item, true), 'reviews' => $this->reviews($item)], 201)
            ->header('Cache-Control', 'private, no-store');
    }

    /** Remove only a file belonging to the current unsent DRAFT. Sent versions are immutable. */
    public function destroyDraftFile(Request $request, int $item, int $file)
    {
        $this->authorizeAccess($request, $item, true);
        $path = DB::transaction(function()use($item, $file) {
            DB::table('sjr_job_items')->where('id', $item)->lockForUpdate()->first();
            $state = DB::table('sjr_job_items')->where('id', $item)->value('design_state');
            if ($state !== 'DRAFT') {
                throw ValidationException::withMessages(['status' => 'Hanya lampiran Draft Aktif yang dapat dihapus.']);
            }
            $found = DB::table('sjr_item_design_files as f')
                ->join('sjr_item_design_submissions as s', 's.id', '=', 'f.submission_id')
                ->where('s.sjr_job_item_id', $item)->where('s.state', 'DRAFT')->where('f.id', $file)
                ->select('f.id', 'f.storage_path')->first();
            abort_unless($found, 404);
            DB::table('sjr_item_design_files')->where('id', $found->id)->delete();

            return $found->storage_path;
        }, 3);
        // Database removal is authoritative; best-effort disk cleanup after commit.
        Storage::disk('local')->delete($path);

        return response()->json(['message' => 'Lampiran Draft berhasil dihapus.', 'file_id' => $file])
            ->header('Cache-Control', 'private, no-store');
    }

    public function download(Request $request, int $item, int $file)
    {
        [$record] = $this->authorizeAccess($request, $item, false, true);
        $found = DB::table('sjr_item_design_files as f')
            ->join('sjr_item_design_submissions as s', 's.id', '=', 'f.submission_id')
            ->where('s.sjr_job_item_id', $item)->where('f.id', $file)
            ->select('f.storage_path', 'f.original_name', 's.state')->first();
        abort_unless($found, 404);
        $isDesigner = $request->user()->roles()->where('code', 'DESIGNER')->exists();
        abort_unless($isDesigner || $found->state === 'IN_REVIEW', 403);
        abort_unless(Storage::disk('local')->exists($found->storage_path), 404);

        return Storage::disk('local')->download($found->storage_path, $found->original_name,
            ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
