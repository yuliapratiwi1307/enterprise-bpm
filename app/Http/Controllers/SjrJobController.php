<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SjrJobController extends Controller
{
    public function download(Request $request, int $job, int $attachment)
    {
        // SJR reference download: MARKETING owner or DESIGNER on designer-required jobs.
        abort_unless(config('database.default') === 'pgsql' &&
         config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 403);
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless(Schema::hasTable('sjr_jobs') && Schema::hasTable('sjr_attachments'), 503);
        $isMarketing = $user->roles()->where('code', 'MARKETING')->exists();
        $isDesigner = $user->roles()->where('code', 'DESIGNER')->exists();
        abort_unless($isMarketing || $isDesigner, 403);
        $query = DB::table('sjr_attachments as a')->join('sjr_jobs as j', 'j.id', '=', 'a.sjr_job_id')
            ->where('a.id', $attachment)->where('j.id', $job);
        $query->where(function($q)use($user, $isMarketing, $isDesigner) {
            if ($isMarketing) {
                $q->where('j.marketing_user_id', $user->id);
            }
            if ($isDesigner) {
                if ($isMarketing) {
                    $q->orWhere('j.requires_designer', true);
                } else {
                    $q->where('j.requires_designer', true);
                }
            }
        });
        $record = $query->select('a.*')->first();
        abort_unless($record, 404);
        abort_unless(Storage::disk('local')->exists($record->storage_path), 404);

        return Storage::disk('local')->download($record->storage_path, $record->original_name);
    }

    private function guard(Request $request, bool $readOnly = false): void
    {
        abort_unless(config('database.connections.pgsql.database') === 'enterprise_bpm_browser' && config('database.default') === 'pgsql', 403, 'SJR hanya boleh menggunakan database pengujian.');
        abort_unless(Schema::hasTable('sjr_jobs') && Schema::hasTable('sjr_attachments') && Schema::hasColumn('sjr_attachments', 'attachment_note'), 503, 'Pembaruan kolom catatan lampiran SJR belum dijalankan.');
        $user = $request->user();
        abort_unless($user, 403);
        $marketing = $user->roles()->where('code', 'MARKETING')->exists();
        $designer = $readOnly && $user->roles()->where('code', 'DESIGNER')->exists();
        abort_unless($marketing || $designer, 403, 'Anda tidak berwenang mengakses data SJR.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->guard($request, true);
        $isMarketing = $request->user()->roles()->where('code', 'MARKETING')->exists();
        $query = DB::table('sjr_jobs');
        if ($isMarketing) {
            $query->where('marketing_user_id', $request->user()->id);
        } else {
            $query->where('requires_designer', true)->whereIn('status', [
                'AWAITING_DESIGNER', 'DESIGN_IN_PROGRESS', 'DESIGN_REVISION', 'DESIGN_REVIEW',
            ]);
        }
        $jobs = $query->orderByDesc('id')->limit(200)->get()->map(fn($j) => [
            'id' => $j->id, 'code' => $j->job_number, 'client' => $j->client_name, 'title' => $j->job_title, 'pic' => $j->client_pic, 'deadline' => $j->target_date, 'details' => $j->brief_details, 'qty' => $j->quantity, 'notes' => $j->notes, 'designer_brief' => $j->designer_brief, 'designer_deadline' => $j->designer_deadline, 'designer' => (bool) $j->requires_designer, 'multi_item' => in_array($j->status, ['MULTI_ITEM_PENDING', 'MULTI_ITEM_SENT'], true), 'status' => match ($j->status ?? '') {
                'AWAITING_DESIGNER' => 'DESIGNER','DESIGN_IN_PROGRESS' => 'DESIGNER','DESIGN_REVIEW' => 'REVIEW','DESIGN_REVISION' => 'REVISION','DESIGN_APPROVED' => 'DESIGN_APPROVED','AWAITING_PRODUCTION' => 'PRODUCTION','MULTI_ITEM_PENDING' => 'MULTI_ITEM_PENDING','MULTI_ITEM_SENT' => 'MULTI_ITEM_SENT',default => ($j->requires_designer ? 'DESIGNER' : 'PRODUCTION')
            }, 'attachments' => DB::table('sjr_attachments')->where('sjr_job_id', $j->id)->orderBy('id')->get(['id', 'original_name as name', 'byte_size as size', 'attachment_note as note'])->toArray(), 'created_at' => $j->created_at,
        ]);

        return response()->json(['jobs' => $jobs, 'role' => $isMarketing ? 'marketing' : 'designer']);
    }

    public function store(Request $request): JsonResponse
    {
        $this->guard($request);
        $data = $request->validate([
            'client' => 'required|string|max:200', 'title' => 'required|string|max:250', 'pic' => 'nullable|string|max:200',
            'deadline' => 'required|date|after_or_equal:today', 'details' => 'required|string|max:10000',
            'qty' => 'required|string|max:100', 'notes' => 'nullable|string|max:2000', 'designer' => 'required|in:yes,no',
            'designer_brief' => 'required_if:designer,yes|nullable|string|max:10000',
            'designer_deadline' => 'required_if:designer,yes|nullable|date',
            'attachments' => 'nullable|array|max:5',
            'attachment_notes' => 'nullable|array|max:5',
            'attachment_notes.*' => 'nullable|string|max:1000',
            'attachments.*' => 'file|max:204800|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip,ai,psd,eps',
        ]);
        $written = [];
        try {
            $id = DB::transaction(function()use($data, $request, &$written) {
                $id = DB::table('sjr_jobs')->insertGetId([
                    'job_number' => (string) Str::uuid(), 'marketing_user_id' => $request->user()->id,
                    'client_name' => $data['client'], 'job_title' => $data['title'], 'client_pic' => $data['pic'] ?? null,
                    'target_date' => $data['deadline'], 'brief_details' => $data['details'], 'quantity' => $data['qty'],
                    'notes' => $data['notes'] ?? null, 'requires_designer' => $data['designer'] === 'yes',
                    'designer_brief' => $data['designer'] === 'yes' ? $data['designer_brief'] : null,
                    'designer_deadline' => $data['designer'] === 'yes' ? $data['designer_deadline'] : null,
                    'status' => $data['designer'] === 'yes' ? 'AWAITING_DESIGNER' : 'AWAITING_PRODUCTION',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('sjr_jobs')->where('id', $id)->update(['job_number' => 'SJR-'.now()->format('Y').'-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT)]);
                foreach ($request->file('attachments', []) as $index => $file) {
                    $name = (string) Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
                    $path = $file->storeAs('sjr/attachments/'.$id, $name, 'local');
                    if (!$path) {
                        throw new \RuntimeException('Gagal menyimpan file lampiran');
                    }
                    $written[] = $path;
                    DB::table('sjr_attachments')->insert([
                        'sjr_job_id' => $id, 'original_name' => substr(basename($file->getClientOriginalName()), 0, 250),
                        'storage_path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'byte_size' => $file->getSize(), 'attachment_note' => ($data['attachment_notes'][$index] ?? null), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                return $id;
            });
        } catch (\Throwable $e) {
            foreach ($written as $path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }
        $record = DB::table('sjr_jobs')->where('id', $id)->first();

        return response()->json(['message' => 'Brief terkirim', 'job' => ['id' => $record->id, 'code' => $record->job_number]], 201);
    }
}
