<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SjrDesignSubmissionController extends Controller
{
    private function guard(Request $request, bool $designerOnly = true): void
    {
        abort_unless(config('database.default') === 'pgsql' && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 403, 'SJR hanya untuk database pengujian.');
        abort_unless($request->user(), 403);
        $role = $designerOnly ? 'DESIGNER' : 'MARKETING';
        abort_unless($request->user()->roles()->where('code', $role)->exists() || (!$designerOnly && $request->user()->roles()->where('code', 'DESIGNER')->exists()), 403, 'Akses role ditolak.');
    }

    public function store(Request $request, string $jobNumber)
    {
        $this->guard($request);
        $data = $request->validate([
            'note' => 'required|string|max:3000', 'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:204800|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,ai,psd,eps',
        ]);
        $stored = [];
        try {
            $result = DB::transaction(function()use($request, $jobNumber, $data, &$stored) {
                $j = DB::table('sjr_jobs')->where('job_number', $jobNumber)->lockForUpdate()->first();
                abort_unless($j && $j->requires_designer, 404);
                if (! in_array($j->status, ['AWAITING_DESIGNER', 'DESIGN_IN_PROGRESS', 'DESIGN_REVISION'], true)) {
                    throw ValidationException::withMessages(['status' => 'Pekerjaan tidak dapat dikirim pada status ini.']);
                }
                $v = ((int) DB::table('sjr_design_submissions')->where('sjr_job_id', $j->id)->max('version')) + 1;
                $id = DB::table('sjr_design_submissions')->insertGetId(['sjr_job_id' => $j->id, 'version' => $v, 'designer_user_id' => $request->user()->id, 'note' => $data['note'], 'created_at' => now(), 'updated_at' => now()]);
                foreach ($request->file('files') as $f) {
                    $path = $f->storeAs('sjr/design-results/'.$j->id.'/'.$v, Str::uuid().'.'.strtolower($f->getClientOriginalExtension()), 'local');
                    if (!$path) {
                        throw new \RuntimeException('Gagal menyimpan berkas');
                    }
                    $stored[] = $path;
                    DB::table('sjr_design_files')->insert(['submission_id' => $id, 'original_name' => substr(basename($f->getClientOriginalName()), 0, 250), 'storage_path' => $path, 'byte_size' => $f->getSize(), 'mime_type' => $f->getMimeType() ?: 'application/octet-stream', 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('sjr_jobs')->where('id', $j->id)->update(['status' => 'DESIGN_REVIEW', 'updated_at' => now()]);

                return ['version' => $v, 'job' => $jobNumber, 'status' => 'DESIGN_REVIEW'];
            }, 3);
        } catch (\Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return response()->json(['message' => 'Hasil desain tersimpan dan dikirim untuk review Marketing', 'result' => $result], 201);
    }

    public function download(Request $request, int $file)
    {
        $this->guard($request, false);
        $record = DB::table('sjr_design_files as f')->join('sjr_design_submissions as s', 's.id', '=', 'f.submission_id')->join('sjr_jobs as j', 'j.id', '=', 's.sjr_job_id')->where('f.id', $file)->select('f.*', 'j.marketing_user_id')->first();
        abort_unless($record, 404);
        $designer = $request->user()->roles()->where('code', 'DESIGNER')->exists();
        abort_unless($designer || (int) $record->marketing_user_id === (int) $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($record->storage_path), 404);

        return Storage::disk('local')->download($record->storage_path, $record->original_name);
    }
}
