<?php

namespace App\Http\Controllers;

use App\Models\DesignRequest;
use App\Services\BpmRoleAuthorization;
use App\Services\DesignRequestWorkflow;
use Illuminate\Http\Request;

class TaskPoolController extends Controller
{
    public function index(Request $request, BpmRoleAuthorization $roles)
    {
        $roles->require($request->user(), 'DESIGNER');
        $tasks = DesignRequest::query()
            ->with(['marketingBrief.clientBrief', 'currentPic'])
            ->where(function ($query) use ($request) {
                $query->where('status', 'AVAILABLE')
                    ->orWhere(function ($assigned) use ($request) {
                        $assigned->where('current_pic_id', $request->user()->id)
                            ->whereIn('status', [
                                'IN_PROGRESS',
                                'SUBMITTED_FOR_REVIEW',
                                'REVISION_REQUIRED',
                                'CANCELLATION_PENDING_ACK',
                            ]);
                    });
            })
            ->orderByRaw("CASE WHEN status = 'AVAILABLE' THEN 0 ELSE 1 END")
            ->orderBy('deadline_at')
            ->paginate(20);

        return view('prod03.task-pool.index', compact('tasks'));
    }

    public function take(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'DESIGNER');
        $workflow->take($designRequest, $request->user());

        return redirect()->route('prod03.design-requests.show', $designRequest)
            ->with('message', 'Tugas berhasil diambil dan ditetapkan kepada Anda.');
    }

    public function release(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'DESIGNER');
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $workflow->release($designRequest, $request->user(), $data['reason']);

        return redirect()->route('prod03.task-pool.index')->with('message', 'Tugas dilepas kembali ke Task Pool; riwayat tetap tersimpan.');
    }

    public function submitResult(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'DESIGNER');
        $uploadLimits = array_filter([
            (int) ini_parse_quantity((string) ini_get('upload_max_filesize')),
            (int) ini_parse_quantity((string) ini_get('post_max_size')),
        ], fn (int $limit): bool => $limit > 0);
        $attachmentRules = ['required', 'file'];
        if ($uploadLimits !== []) {
            $attachmentRules[] = 'max:'.max(1, (int) floor(min($uploadLimits) / 1024));
        }
        $data = $request->validate(['attachment' => $attachmentRules]);
        $workflow->submitResult($designRequest, $request->user(), $data['attachment']);

        return back()->with('message', 'Hasil dan versi lampiran berhasil dikirim untuk review Marketing.');
    }

    public function review(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'MARKETING');
        abort_unless(
            $designRequest->marketingBrief()->where('marketing_user_id', $request->user()->id)->exists(),
            403
        );
        $data = $request->validate([
            'action' => ['required', 'in:accept,revision'],
            'reason' => ['nullable', 'required_if:action,revision', 'string', 'max:2000'],
        ]);
        $workflow->review($designRequest, $request->user(), $data['action'], $data['reason'] ?? null);

        return back()->with('message', $data['action'] === 'accept'
            ? 'Hasil diterima dan Design Request selesai.'
            : 'Permintaan revisi dikirim kepada PIC Desainer sebelumnya.');
    }

    public function requestDeadlineChange(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'DESIGNER');
        $data = $request->validate([
            'requested_deadline_at' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $workflow->requestDeadlineChange(
            $designRequest,
            $request->user(),
            $data['requested_deadline_at'],
            $data['reason'],
        );

        return back()->with('message', 'Permintaan perubahan deadline dikirim ke Marketing.');
    }

    public function acknowledgeCancellation(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'DESIGNER');
        $workflow->acknowledgeCancellation($designRequest, $request->user());

        return back()->with('message', 'Penerimaan instruksi penghentian dikonfirmasi; tugas menjadi CANCELLED.');
    }
}
