<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SjrProductionTechnicalController extends Controller
{
    private const ROLES = ['PRODUCTION', 'PRODUCTION_ADMIN', 'PRODUCTION_MANAGER', 'ADMIN_PRODUKSI', 'MANAGER_PRODUKSI', 'ASST_MANAGER_PRODUKSI', 'ASSISTANT_MANAGER_PRODUKSI', 'PRODUKSI'];

    private function guard(Request $request): void
    {
        abort_unless(app()->environment('local') && config('database.default') === 'pgsql' && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 403, 'Hanya untuk lingkungan pengujian SJR.');
        abort_unless($request->user() && $request->user()->roles()->whereIn('code', self::ROLES)->exists(), 403, 'Hanya akun Produksi SJR yang berwenang. Periksa kode role lokal sebelum menambahkan ke daftar izin.');
        abort_unless(Schema::hasTable('sjr_production_brief_reviews') && Schema::hasTable('sjr_technical_drafts'), 503, 'Migrasi tahap Persiapan Teknis SJR belum dijalankan.');
    }

    private function job(string $number)
    {
        $job = DB::table('sjr_jobs')->where('job_number', $number)->first();
        abort_unless($job, 404);
        // No handover for designs not yet approved. Non-designer jobs are immediately eligible.
        abort_unless(($job->requires_designer && $job->status === 'DESIGN_APPROVED') || (!$job->requires_designer && $job->status === 'AWAITING_PRODUCTION'), 409, 'Pekerjaan belum memenuhi syarat masuk tahap Produksi.');

        return $job;
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $jobs = DB::table('sjr_jobs as j')->leftJoin('sjr_production_brief_reviews as r', 'r.sjr_job_id', '=', 'j.id')
            ->where(fn($q) => $q->where(fn($x) => $x->where('j.requires_designer', true)->where('j.status', 'DESIGN_APPROVED'))
                ->orWhere(fn($x) => $x->where('j.requires_designer', false)->where('j.status', 'AWAITING_PRODUCTION')))
            ->orderByDesc('j.id')->select('j.job_number', 'j.job_title', 'j.client_name', 'j.target_date', 'j.requires_designer', 'r.decision')->get();

        return view('sjr.production-technical-index', compact('jobs'));
    }

    public function show(Request $request, string $jobNumber)
    {
        $this->guard($request);
        $job = $this->job($jobNumber);
        $review = DB::table('sjr_production_brief_reviews')->where('sjr_job_id', $job->id)->first();
        $draft = DB::table('sjr_technical_drafts')->where('sjr_job_id', $job->id)->first();
        $attachments = DB::table('sjr_attachments')->where('sjr_job_id', $job->id)->orderBy('id')->get();
        $designFiles = collect();
        if ($job->requires_designer) {
            $latest = DB::table('sjr_design_submissions')->where('sjr_job_id', $job->id)->orderByDesc('version')->first();
            if ($latest) {
                $designFiles = DB::table('sjr_design_files')->where('submission_id', $latest->id)->get();
            }
        }

        return view('sjr.production-technical', compact('job', 'review', 'draft', 'attachments', 'designFiles'));
    }

    public function review(Request $request, string $jobNumber)
    {
        $this->guard($request);
        $job = $this->job($jobNumber);
        $data = $request->validate(['decision' => 'required|in:clear,clarification', 'note' => 'nullable|string|max:3000']);
        if ($data['decision'] === 'clarification' && trim((string) ($data['note'] ?? '')) === '') {
            throw ValidationException::withMessages(['note' => 'Catatan klarifikasi wajib diisi.']);
        }
        DB::transaction(function()use($job, $request, $data) {
            DB::table('sjr_jobs')->where('id', $job->id)->lockForUpdate()->first();
            $exists = DB::table('sjr_technical_drafts')->where('sjr_job_id', $job->id)->where('state', 'READY_FOR_HPP')->exists();
            if ($exists) {
                throw ValidationException::withMessages(['decision' => 'Persiapan teknis sudah dikonfirmasi siap HPP.']);
            }
            DB::table('sjr_production_brief_reviews')->updateOrInsert(['sjr_job_id' => $job->id], [
                'decision' => $data['decision'] === 'clear' ? 'CLEAR' : 'CLARIFICATION',
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
                'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);

        return redirect()->route('sjr.production.technical.show', ['jobNumber' => $jobNumber])->with('message', 'Keputusan pemeriksaan brief disimpan.');
    }

    public function save(Request $request, string $jobNumber)
    {
        $this->guard($request);
        $job = $this->job($jobNumber);
        $review = DB::table('sjr_production_brief_reviews')->where('sjr_job_id', $job->id)->first();
        abort_unless($review && $review->decision === 'CLEAR', 409, 'Brief harus dikonfirmasi cukup jelas dahulu.');
        $data = $request->validate([
            'materials' => 'nullable|array|max:100', 'materials.*.name' => 'nullable|string|max:200', 'materials.*.spec' => 'nullable|string|max:1000', 'materials.*.unit' => 'nullable|string|max:50', 'materials.*.quantity' => 'nullable|numeric|gt:0|max:99999999',
            'processes' => 'nullable|array|max:100', 'processes.*.name' => 'nullable|string|max:200', 'processes.*.spec' => 'nullable|string|max:1000', 'processes.*.unit' => 'nullable|string|max:50', 'processes.*.quantity' => 'nullable|numeric|gt:0|max:99999999',
            'instructions' => 'nullable|string|max:10000', 'leadtime_days' => 'nullable|integer|min:1|max:3650', 'leadtime_note' => 'nullable|string|max:2000',
            'action' => 'required|in:draft,ready',
        ]);
        $materials = $this->rows($data['materials'] ?? []);
        $processes = $this->rows($data['processes'] ?? []);
        if ($data['action'] === 'ready' && (!$materials || !$processes || !trim((string) ($data['instructions'] ?? '')) || empty($data['leadtime_days']))) {
            throw ValidationException::withMessages(['action' => 'Untuk Siap Hitung HPP, lengkapi minimal satu material dan proses/jasa serta instruksi dan leadtime.']);
        }
        DB::transaction(function()use($job, $request, $data, $materials, $processes) {
            DB::table('sjr_jobs')->where('id', $job->id)->lockForUpdate()->first();
            $existing = DB::table('sjr_technical_drafts')->where('sjr_job_id', $job->id)->first();
            if ($existing && $existing->state === 'READY_FOR_HPP') {
                throw ValidationException::withMessages(['action' => 'Data sudah siap HPP dan dikunci.']);
            }
            $record = ['materials_json' => json_encode($materials, JSON_UNESCAPED_UNICODE), 'processes_json' => json_encode($processes, JSON_UNESCAPED_UNICODE),
                'instructions' => trim((string) ($data['instructions'] ?? '')), 'leadtime_days' => $data['leadtime_days'] ?? null, 'leadtime_note' => trim((string) ($data['leadtime_note'] ?? '')),
                'state' => $data['action'] === 'ready' ? 'READY_FOR_HPP' : 'DRAFT', 'saved_by' => $request->user()->id, 'updated_at' => now()];
            if ($existing) {
                DB::table('sjr_technical_drafts')->where('id', $existing->id)->update($record);
            } else {
                DB::table('sjr_technical_drafts')->insert(array_merge(['sjr_job_id' => $job->id, 'created_at' => now()], $record));
            }
        }, 3);

        return redirect()->route('sjr.production.technical.show', ['jobNumber' => $jobNumber])->with('message', $data['action'] === 'ready' ? 'Persiapan teknis dikunci: Siap Hitung HPP.' : 'Draft persiapan teknis tersimpan.');
    }

    private function rows(array $items): array
    {
        $result = [];
        foreach ($items as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            $spec = trim((string) ($r['spec'] ?? ''));
            $unit = trim((string) ($r['unit'] ?? ''));
            $qty = $r['quantity'] ?? null;
            if ($name === '' && $spec === '' && $unit === '' && ($qty === null || $qty === '')) {
                continue;
            }
            if ($name === '' || $unit === '' || ! is_numeric($qty) || $qty <= 0) {
                throw ValidationException::withMessages(['materials' => 'Setiap baris harus berisi nama, satuan, dan kuantitas positif.']);
            }
            $result[] = ['name' => $name, 'spec' => $spec, 'unit' => $unit, 'quantity' => (float) $qty];
        }

        return $result;
    }
}
