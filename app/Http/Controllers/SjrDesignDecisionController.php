<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SjrDesignDecisionController extends Controller
{
    public function store(Request $request, string $jobNumber)
    {
        abort_unless(config('database.default') === 'pgsql' && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 403, 'Hanya untuk database pengujian.');
        abort_unless($request->user() && $request->user()->roles()->where('code', 'MARKETING')->exists(), 403, 'Hanya Marketing dapat memutuskan hasil desain.');
        abort_unless(Schema::hasTable('sjr_design_decisions'), 503, 'Migrasi review Marketing belum dipasang.');
        $data = $request->validate(['action' => 'required|in:revision,approve', 'note' => 'nullable|string|max:3000']);
        if ($data['action'] === 'revision' && trim((string) ($data['note'] ?? '')) === '') {
            throw ValidationException::withMessages(['note' => 'Catatan revisi wajib diisi.']);
        }
        $result = DB::transaction(function () use ($request, $data, $jobNumber) {
            $job = DB::table('sjr_jobs')->where('job_number', $jobNumber)->lockForUpdate()->first();
            abort_unless($job && $job->requires_designer, 404);
            abort_unless((int) $job->marketing_user_id === (int) $request->user()->id, 403, 'Pekerjaan ini bukan milik akun Marketing Anda.');
            if ($job->status !== 'DESIGN_REVIEW') {
                throw ValidationException::withMessages(['status' => 'Pekerjaan harus berstatus Review Marketing. Muat ulang halaman.']);
            }
            $latest = DB::table('sjr_design_submissions')->where('sjr_job_id', $job->id)->orderByDesc('version')->first();
            if (! $latest) {
                throw ValidationException::withMessages(['status' => 'Belum ada hasil desain yang dapat direview.']);
            }
            $exists = DB::table('sjr_design_decisions')->where('submission_id', $latest->id)->exists();
            if ($exists) {
                throw ValidationException::withMessages(['status' => 'Versi ini sudah diputuskan. Muat ulang halaman.']);
            }
            $revision = $data['action'] === 'revision';
            DB::table('sjr_design_decisions')->insert([
                'sjr_job_id' => $job->id, 'submission_id' => $latest->id,
                'submission_version' => $latest->version, 'marketing_user_id' => $request->user()->id,
                'action' => $revision ? 'REVISION_REQUESTED' : 'APPROVED',
                'note' => $revision ? trim($data['note']) : trim((string) ($data['note'] ?? '')),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $status = $revision ? 'DESIGN_REVISION' : 'DESIGN_APPROVED';
            DB::table('sjr_jobs')->where('id', $job->id)->update(['status' => $status, 'updated_at' => now()]);

            return ['job' => $jobNumber, 'version' => (int) $latest->version, 'status' => $status];
        }, 3);

        return response()->json(['message' => 'Keputusan Marketing berhasil disimpan', 'result' => $result]);
    }
}
