<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SjrDesignWorkspaceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(config('database.default') === 'pgsql' &&
            config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 403,
            'Design Workspace hanya untuk database pengujian.');
        abort_unless($request->user(), 403);
        abort_unless(Schema::hasTable('sjr_jobs') && Schema::hasTable('sjr_attachments') && Schema::hasTable('sjr_design_submissions') && Schema::hasTable('sjr_design_files'), 503);
        $marketing = $request->user()->roles()->where('code', 'MARKETING')->exists();
        $designer = $request->user()->roles()->where('code', 'DESIGNER')->exists();
        abort_unless($marketing || $designer, 403, 'Akses hanya Marketing / Designer SJR.');
        $q = DB::table('sjr_jobs')->where('requires_designer', true);
        if (! $designer) {
            $q->where('marketing_user_id', $request->user()->id);
        }
        $jobs = $q->orderByDesc('id')->limit(100)->get()->map(function ($j) {
            $status = match ($j->status ?? '') {
                'AWAITING_DESIGNER' => 'new',
                'DESIGN_IN_PROGRESS' => 'progress',
                'DESIGN_REVIEW' => 'review',
                'DESIGN_REVISION' => 'revision',
                'DESIGN_APPROVED' => 'done',
                default => 'new',
            };
            $activities = DB::table('sjr_design_submissions as s')
                ->join('users as u', 'u.id', '=', 's.designer_user_id')
                ->where('s.sjr_job_id', $j->id)
                ->orderBy('s.id')->select('s.version', 's.created_at', 'u.name')->get()
                ->map(fn ($s) => ['message' => 'Designer menyerahkan hasil desain Versi '.$s->version.' ke Marketing',
                    'by' => (string) $s->name, 'at' => (string) $s->created_at])->all();
            $decisions = DB::table('sjr_design_decisions as d')
                ->join('users as u', 'u.id', '=', 'd.marketing_user_id')
                ->where('d.sjr_job_id', $j->id)
                ->orderBy('d.id')->select('d.*', 'u.name')->get();
            foreach ($decisions as $d) {
                $activities[] = ['message' => $d->action === 'REVISION_REQUESTED'
                     ? 'Marketing meminta revisi desain Versi '.$d->submission_version.': '.$d->note
                     : 'Marketing menyetujui desain Versi '.$d->submission_version,
                    'by' => (string) $d->name, 'at' => (string) $d->created_at];
            }
            usort($activities, fn ($a, $b) => strcmp($a['at'], $b['at']));
            array_unshift($activities, ['message' => 'Brief diterima oleh divisi Designer', 'by' => 'Sistem', 'at' => 'Data pengujian']);
            $attachments = DB::table('sjr_attachments')->where('sjr_job_id', $j->id)
                ->orderBy('id')->get();
            $versions = DB::table('sjr_design_submissions')->where('sjr_job_id', $j->id)->orderBy('version')->get()->map(function ($v) {
                $files = DB::table('sjr_design_files')->where('submission_id', $v->id)->orderBy('id')->get();

                return ['version' => (int) $v->version, 'note' => $v->note, 'sentAt' => (string) $v->created_at,
                    'files' => $files->map(fn ($f) => ['name' => $f->original_name, 'sizeText' => round($f->byte_size / 1048576, 2).' MB', 'downloadUrl' => route('sjr.design.files.download', ['file' => $f->id])])->all()];
            })->all();

            return [
                'revisions' => $decisions->filter(fn ($d) => $d->action === 'REVISION_REQUESTED')->values()->map(fn ($d) => [
                    'number' => (int) $d->submission_version, 'note' => (string) $d->note, 'at' => (string) $d->created_at,
                ])->all(),
                'submissions' => $versions,
                'history' => $activities,
                'id' => (string) $j->job_number,
                'name' => (string) $j->job_title,
                'client' => (string) $j->client_name,
                'pic' => (string) ($j->client_pic ?? '—'),
                'target' => (string) $j->target_date,
                'deadline' => (string) ($j->designer_deadline ?? $j->target_date),
                'brief' => (string) ($j->designer_brief ?? ''),
                'spec' => strip_tags((string) ($j->brief_details ?? '')),
                'quantity' => (string) $j->quantity,
                'status' => $status,
                'files' => $attachments->map(fn ($a) => [
                    (string) $a->original_name,
                    (string) ($a->attachment_note ?? ''),
                    route('sjr.reference.download', ['job' => $j->id, 'attachment' => $a->id]),
                    round(((int) ($a->byte_size ?? 0)) / 1048576, 2).' MB',
                ])->all(),
            ];
        })->all();
        $isDesigner = $designer;

        return view('sjr.design-workspace', compact('jobs', 'isDesigner'));
    }

    /** Read-only job activity for the existing Marketing Job Workspace. */
    public function history(Request $request)
    {
        abort_unless(config('database.default') === 'pgsql' &&
            config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 403);
        $user = $request->user();
        abort_unless($user && $user->roles()->where('code', 'MARKETING')->exists(), 403);
        abort_unless(Schema::hasTable('sjr_jobs') && Schema::hasTable('sjr_design_submissions') &&
            Schema::hasTable('sjr_design_decisions'), 503);
        $jobs = DB::table('sjr_jobs')->where('marketing_user_id', $user->id)
            ->where('requires_designer', true)->select('id', 'job_number')->get();
        $result = [];
        foreach ($jobs as $job) {
            $events = [];
            $submissions = DB::table('sjr_design_submissions as s')
                ->join('users as u', 'u.id', '=', 's.designer_user_id')
                ->where('s.sjr_job_id', $job->id)
                ->select('s.version', 's.created_at', 'u.name')->get();
            foreach ($submissions as $s) {
                $events[] = [
                    'message' => 'Designer menyerahkan hasil desain Versi '.$s->version.' ke Marketing',
                    'by' => $s->name, 'at' => (string) $s->created_at,
                ];
            }
            $decisions = DB::table('sjr_design_decisions as d')
                ->join('users as u', 'u.id', '=', 'd.marketing_user_id')
                ->where('d.sjr_job_id', $job->id)
                ->select('d.action', 'd.submission_version', 'd.note', 'd.created_at', 'u.name')->get();
            foreach ($decisions as $d) {
                $events[] = [
                    'message' => $d->action === 'REVISION_REQUESTED'
                        ? 'Marketing meminta revisi desain Versi '.$d->submission_version.': '.$d->note
                        : 'Marketing menyetujui desain Versi '.$d->submission_version,
                    'by' => $d->name, 'at' => (string) $d->created_at,
                ];
            }
            usort($events, fn ($x, $y) => strcmp($x['at'], $y['at']));
            $result[$job->job_number] = $events;
        }

        return response()->json(['jobs' => $result]);
    }

    /** Read-only SJR result metadata for approved Detail Pekerjaan V3. */
    public function detail(Request $request)
    {
        abort_unless(config('database.default') === 'pgsql' &&
            config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 403);
        $user = $request->user();
        abort_unless($user && $user->roles()->where('code', 'MARKETING')->exists(), 403);
        $number = (string) $request->query('sjr', '');
        abort_unless(preg_match('/^SJR-\d{4}-\d{5}$/', $number), 404);
        $job = DB::table('sjr_jobs')->where('job_number', $number)
            ->where('marketing_user_id', $user->id)->where('requires_designer', true)->first();
        abort_unless($job, 404);
        $submissions = DB::table('sjr_design_submissions')
            ->where('sjr_job_id', $job->id)->orderBy('version')->get()->map(function ($v) {
                $files = DB::table('sjr_design_files')->where('submission_id', $v->id)
                    ->orderBy('id')->get()->map(fn ($f) => [
                        'name' => (string) $f->original_name,
                        'sizeText' => round((int) $f->byte_size / 1048576, 2).' MB',
                        'downloadUrl' => route('sjr.design.files.download', ['file' => $f->id]),
                    ])->all();

                return ['version' => (int) $v->version, 'note' => (string) ($v->note ?? ''),
                    'sentAt' => (string) $v->created_at, 'files' => $files];
            })->all();

        return response()->json(['submissions' => $submissions]);
    }
}
