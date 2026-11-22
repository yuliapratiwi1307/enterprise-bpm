<?php

namespace App\Http\Controllers;

use App\Models\DesignRequest;
use App\Models\DesignRequestAttachment;
use App\Models\DesignRequestDeadlineChange;
use App\Models\MarketingBrief;
use App\Services\BpmRoleAuthorization;
use App\Services\DesignRequestWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DesignRequestController extends Controller
{
    public function create(Request $request, MarketingBrief $marketingBrief, BpmRoleAuthorization $roles)
    {
        $roles->require($request->user(), 'MARKETING');
        $this->authorizeOwner($request, $marketingBrief);
        abort_unless($marketingBrief->status !== 'SUBMITTED', 403);

        return view('prod03.design-requests.form', compact('marketingBrief'));
    }

    public function store(
        Request $request,
        MarketingBrief $marketingBrief,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'MARKETING');
        $this->authorizeOwner($request, $marketingBrief);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'classification' => ['required', Rule::in(['CRITICAL', 'NONCRITICAL'])],
            'classification_reason' => ['required', 'string', 'max:2000'],
            'impact' => ['required', 'string', 'max:2000'],
            'deadline_at' => ['required', 'date', 'after:now'],
        ]);

        $designRequest = $workflow->create(
            $marketingBrief,
            $request->user(),
            $data['title'],
            $data['description'],
            $data['classification'],
            $data['classification_reason'],
            $data['impact'],
            $data['deadline_at'],
        );

        return redirect()->route('prod03.design-requests.show', $designRequest)
            ->with('message', 'Design Request berhasil dibuat dan tersedia di Task Pool.');
    }

    public function show(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
    ) {
        $this->authorizeViewer($request, $designRequest, $roles);
        $designRequest->load([
            'marketingBrief.clientBrief',
            'currentPic',
            'assignments.designer',
            'attachments.uploader',
            'deadlineChanges',
            'events.actor',
        ]);
        $pendingDeadlineChange = $designRequest->deadlineChanges()->where('status', 'PENDING')->first();
        $canReview = $roles->has($request->user(), 'MARKETING')
            && $designRequest->marketingBrief->marketing_user_id === $request->user()->id;
        $isAssignedDesigner = $roles->has($request->user(), 'DESIGNER')
            && $designRequest->current_pic_id === $request->user()->id;

        return view('prod03.design-requests.show', compact(
            'designRequest',
            'pendingDeadlineChange',
            'canReview',
            'isAssignedDesigner',
        ));
    }

    public function changeClassification(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'MARKETING');
        $this->authorizeOwner($request, $designRequest->marketingBrief);
        $data = $request->validate([
            'classification' => ['required', Rule::in(['CRITICAL', 'NONCRITICAL'])],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $workflow->changeClassification($designRequest, $request->user(), $data['classification'], $data['reason']);

        return back()->with('message', 'Klasifikasi dan alasan perubahan berhasil dicatat.');
    }

    public function cancel(
        Request $request,
        DesignRequest $designRequest,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'MARKETING');
        $this->authorizeOwner($request, $designRequest->marketingBrief);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'replacement_information' => ['nullable', 'required_with:replacement_source', 'string', 'max:10000'],
            'replacement_source' => ['nullable', 'required_with:replacement_information', 'string', 'max:255'],
        ]);
        $result = $workflow->cancel(
            $designRequest,
            $request->user(),
            $data['reason'],
            $data['replacement_information'] ?? null,
            $data['replacement_source'] ?? null,
        );

        return back()->with(
            'message',
            $result->status === 'CANCELLED'
                ? 'Design Request yang tersedia langsung dibatalkan.'
                : 'Instruksi penghentian dikirim; menunggu konfirmasi penerimaan Desainer.'
        );
    }

    public function decideDeadline(
        Request $request,
        DesignRequest $designRequest,
        DesignRequestDeadlineChange $deadlineChange,
        BpmRoleAuthorization $roles,
        DesignRequestWorkflow $workflow,
    ) {
        $roles->require($request->user(), 'MARKETING');
        abort_unless($deadlineChange->design_request_id === $designRequest->id, 404);
        $this->authorizeOwner($request, $deadlineChange->designRequest->marketingBrief);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'decision_reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $workflow->decideDeadlineChange(
            $deadlineChange,
            $request->user(),
            $data['decision'] === 'approve',
            $data['decision_reason'] ?? null,
        );

        return back()->with('message', 'Keputusan perubahan deadline berhasil dicatat.');
    }

    public function downloadAttachment(
        Request $request,
        DesignRequestAttachment $attachment,
        BpmRoleAuthorization $roles,
    ) {
        $designRequest = $attachment->designRequest;
        $this->authorizeViewer($request, $designRequest, $roles);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name);
    }

    private function authorizeOwner(Request $request, MarketingBrief $brief): void
    {
        abort_unless($brief->marketing_user_id === $request->user()->id, 403);
    }

    private function authorizeViewer(Request $request, DesignRequest $designRequest, BpmRoleAuthorization $roles): void
    {
        $user = $request->user();
        $isMarketingOwner = $roles->has($user, 'MARKETING')
            && $designRequest->marketingBrief()->where('marketing_user_id', $user->id)->exists();
        $isDesigner = $roles->has($user, 'DESIGNER')
            && $designRequest->assignments()->where('designer_user_id', $user->id)->exists();

        abort_unless($isMarketingOwner || $isDesigner, 403);
    }
}
