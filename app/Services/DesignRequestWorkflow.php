<?php

namespace App\Services;

use App\Models\DesignRequest;
use App\Models\DesignRequestAssignment;
use App\Models\DesignRequestAttachment;
use App\Models\DesignRequestDeadlineChange;
use App\Models\MarketingBrief;
use App\Models\User;
use App\Notifications\ProductionWorkflowNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DesignRequestWorkflow
{
    public function __construct(
        private readonly ProductionWorkflowAudit $audit,
        private readonly MarketingBriefReadiness $readiness,
    ) {}

    public function create(
        MarketingBrief $brief,
        User $marketing,
        string $title,
        string $description,
        string $classification,
        string $classificationReason,
        string $impact,
        string $deadline,
    ): DesignRequest {
        return DB::transaction(function () use ($brief, $marketing, $title, $description, $classification, $classificationReason, $impact, $deadline) {
            $brief = MarketingBrief::query()->whereKey($brief->id)->lockForUpdate()->firstOrFail();
            $this->assertMarketingOwner($brief, $marketing);
            if ($brief->status === 'SUBMITTED') {
                throw ValidationException::withMessages(['brief' => 'Brief yang telah dikirim tidak dapat diubah pada PROD-03.']);
            }

            $request = $brief->designRequests()->create([
                'created_by' => $marketing->id,
                'title' => $title,
                'description' => $description,
                'classification' => $classification,
                'classification_reason' => $classificationReason,
                'impact' => $impact,
                'status' => 'AVAILABLE',
                'deadline_at' => $deadline,
            ]);
            $this->audit->record(
                $brief,
                $request,
                $marketing,
                'MARKETING',
                'DESIGN_REQUEST_CREATED',
                null,
                [
                    'title' => $request->title,
                    'description' => $request->description,
                    'classification' => $classification,
                    'recommended_classification' => null,
                    'deadline_at' => $request->deadline_at?->toISOString(),
                    'status' => $request->status,
                ],
                $classificationReason,
            );
            $this->audit->record(
                $brief,
                $request,
                $marketing,
                'MARKETING',
                'CLASSIFICATION_SET',
                null,
                ['recommended_classification' => null, 'classification' => $classification],
                $classificationReason,
            );
            $this->refreshBrief($request, $marketing);

            return $request;
        });
    }

    public function changeClassification(
        DesignRequest $designRequest,
        User $marketing,
        string $classification,
        string $reason,
    ): DesignRequest {
        return DB::transaction(function () use ($designRequest, $marketing, $classification, $reason) {
            $request = DesignRequest::query()->whereKey($designRequest->id)->lockForUpdate()->firstOrFail();
            $this->assertMarketingOwner($request->marketingBrief, $marketing);
            if ($request->marketingBrief->status === 'SUBMITTED') {
                throw ValidationException::withMessages([
                    'classification' => 'Perubahan setelah pengiriman Brief harus melalui proses revisi dan pemeriksaan dampak PROD-05.',
                ]);
            }
            if (in_array($request->status, ['COMPLETED', 'CANCELLED', 'CANCELLATION_PENDING_ACK'], true)) {
                throw ValidationException::withMessages(['classification' => 'Klasifikasi tugas terminal tidak dapat diubah.']);
            }
            $before = ['classification' => $request->classification];
            $request->classification = $classification;
            $request->classification_reason = $reason;
            $request->save();
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $marketing,
                'MARKETING',
                'CLASSIFICATION_CHANGED',
                $before,
                ['classification' => $classification, 'recommended_classification' => $request->recommended_classification],
                $reason,
            );
            $this->refreshBrief($request, $marketing);

            return $request;
        });
    }

    public function take(DesignRequest $designRequest, User $designer): DesignRequest
    {
        return DB::transaction(function () use ($designRequest, $designer) {
            $request = DesignRequest::query()->whereKey($designRequest->id)->lockForUpdate()->firstOrFail();
            if (
                $request->status !== 'AVAILABLE'
                || $request->assignments()->whereNull('released_at')->exists()
            ) {
                throw ValidationException::withMessages(['task' => 'Tugas tidak lagi tersedia di Task Pool.']);
            }

            $request->status = 'IN_PROGRESS';
            $request->current_pic_id = $designer->id;
            $request->save();

            $assignment = DesignRequestAssignment::query()->create([
                'design_request_id' => $request->id,
                'designer_user_id' => $designer->id,
                'assigned_at' => now(),
            ]);

            $this->audit->record(
                $request->marketingBrief,
                $request,
                $designer,
                'DESIGNER',
                'TASK_TAKEN',
                ['status' => 'AVAILABLE', 'current_pic_id' => null],
                ['status' => $request->status, 'current_pic_id' => $designer->id, 'assignment_id' => $assignment->id],
            );

            return $request;
        });
    }

    public function release(DesignRequest $designRequest, User $designer, string $reason): DesignRequest
    {
        return DB::transaction(function () use ($designRequest, $designer, $reason) {
            $request = DesignRequest::query()->whereKey($designRequest->id)->lockForUpdate()->firstOrFail();
            $this->assertAssignedDesigner($request, $designer);
            $this->assertWorkCanContinue($request);
            if (! in_array($request->status, ['IN_PROGRESS', 'REVISION_REQUIRED'], true)) {
                throw ValidationException::withMessages(['task' => 'Task hanya dapat dilepas saat sedang dikerjakan.']);
            }
            $before = ['status' => $request->status, 'current_pic_id' => $request->current_pic_id];
            $this->activeAssignment($request, $designer)->update([
                'released_at' => now(),
                'release_reason' => $reason,
            ]);
            $request->status = 'AVAILABLE';
            $request->current_pic_id = null;
            $request->save();
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $designer,
                'DESIGNER',
                'TASK_RELEASED',
                $before,
                ['status' => $request->status, 'current_pic_id' => null],
                $reason,
            );
            $this->refreshBrief($request, $designer);

            return $request;
        });
    }

    public function submitResult(DesignRequest $designRequest, User $designer, UploadedFile $file): DesignRequestAttachment
    {
        $path = null;
        try {
            return DB::transaction(function () use ($designRequest, $designer, $file, &$path) {
                $request = DesignRequest::query()->whereKey($designRequest->id)->lockForUpdate()->firstOrFail();
                $this->assertAssignedDesigner($request, $designer);
                $this->assertWorkCanContinue($request);

                if (! in_array($request->status, ['IN_PROGRESS', 'REVISION_REQUIRED'], true)) {
                    throw ValidationException::withMessages(['task' => 'Hasil hanya dapat dikirim saat tugas sedang dikerjakan.']);
                }

                $version = (int) $request->attachments()->max('version') + 1;
                $directory = "production/design-requests/{$request->id}";
                $path = $file->store($directory, 'local');
                if (! is_string($path)) {
                    throw new RuntimeException('Penyimpanan lampiran Design Request gagal.');
                }

                $attachment = DesignRequestAttachment::query()->create([
                    'design_request_id' => $request->id,
                    'version' => $version,
                    'uploaded_by' => $designer->id,
                    'original_name' => $file->getClientOriginalName(),
                    'storage_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'created_at' => now(),
                ]);
                $before = ['status' => $request->status];
                $request->status = 'SUBMITTED_FOR_REVIEW';
                $request->save();
                $this->audit->record(
                    $request->marketingBrief,
                    $request,
                    $designer,
                    'DESIGNER',
                    'DESIGN_RESULT_SUBMITTED',
                    $before,
                    ['status' => $request->status, 'attachment_id' => $attachment->id, 'version' => $version],
                    null,
                    $version,
                );
                $this->notifyMarketing($request, 'DESIGN_RESULT_SUBMITTED', "Hasil Design Request #{$request->id} siap ditinjau.");

                return $attachment;
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    public function review(
        DesignRequest $designRequest,
        User $marketing,
        string $action,
        ?string $reason = null,
    ): DesignRequest {
        return DB::transaction(function () use ($designRequest, $marketing, $action, $reason) {
            $request = DesignRequest::query()->whereKey($designRequest->id)->lockForUpdate()->firstOrFail();
            $this->assertMarketingOwner($request->marketingBrief, $marketing);
            if ($request->status !== 'SUBMITTED_FOR_REVIEW') {
                throw ValidationException::withMessages(['task' => 'Tidak ada hasil yang sedang menunggu review.']);
            }

            $assignedDesigner = $request->currentPic;
            $before = ['status' => $request->status, 'current_pic_id' => $request->current_pic_id];
            if ($action === 'accept') {
                if ($assignedDesigner === null) {
                    throw ValidationException::withMessages(['task' => 'PIC aktif tidak ditemukan untuk menutup assignment.']);
                }
                $this->activeAssignment($request, $assignedDesigner)->update([
                    'released_at' => now(),
                    'release_reason' => 'Hasil diterima Marketing.',
                ]);
                $request->status = 'COMPLETED';
                $request->current_pic_id = null;
                $event = 'DESIGN_RESULT_ACCEPTED';
                $message = "Hasil Design Request #{$request->id} diterima.";
                $this->closePendingDeadlineChanges($request, $marketing, 'Hasil diterima; perubahan deadline tidak lagi diperlukan.');
            } elseif ($action === 'revision') {
                $request->status = 'REVISION_REQUIRED';
                $event = 'REVISION_REQUESTED';
                $message = "Revisi diminta untuk Design Request #{$request->id}.";
            } else {
                throw ValidationException::withMessages(['action' => 'Keputusan review tidak valid.']);
            }
            $request->save();
            $version = (int) ($request->attachments()->max('version') ?? 0);
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $marketing,
                'MARKETING',
                $event,
                $before,
                [
                    'status' => $request->status,
                    'current_pic_id' => $request->current_pic_id,
                    'completed_pic_id' => $action === 'accept' ? $assignedDesigner?->id : null,
                    'latest_attachment_version' => $version,
                ],
                $reason,
                $version ?: null,
            );
            $this->notify($assignedDesigner, $event, $message, ['design_request_id' => $request->id]);
            $this->refreshBrief($request, $marketing);

            return $request;
        });
    }

    public function requestDeadlineChange(
        DesignRequest $designRequest,
        User $designer,
        string $deadline,
        string $reason,
    ): DesignRequestDeadlineChange {
        return DB::transaction(function () use ($designRequest, $designer, $deadline, $reason) {
            $request = DesignRequest::query()->whereKey($designRequest->id)->lockForUpdate()->firstOrFail();
            $this->assertAssignedDesigner($request, $designer);
            $this->assertWorkCanContinue($request);
            if (! in_array($request->status, ['IN_PROGRESS', 'REVISION_REQUIRED'], true)) {
                throw ValidationException::withMessages(['deadline' => 'Perubahan deadline hanya dapat diminta saat tugas dikerjakan.']);
            }
            if ($request->deadlineChanges()->where('status', 'PENDING')->exists()) {
                throw ValidationException::withMessages(['deadline' => 'Masih ada permintaan perubahan deadline yang menunggu keputusan.']);
            }
            $change = $request->deadlineChanges()->create([
                'requested_by' => $designer->id,
                'previous_deadline_at' => $request->deadline_at,
                'requested_deadline_at' => $deadline,
                'request_reason' => $reason,
            ]);
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $designer,
                'DESIGNER',
                'DEADLINE_CHANGE_REQUESTED',
                ['deadline_at' => $request->deadline_at?->toISOString()],
                ['deadline_change_id' => $change->id, 'requested_deadline_at' => $change->requested_deadline_at->toISOString()],
                $reason,
            );
            $this->notify($request->marketingBrief->marketing, 'DEADLINE_CHANGE_REQUESTED', "Perubahan deadline Design Request #{$request->id} menunggu keputusan.", ['design_request_id' => $request->id]);

            return $change;
        });
    }

    public function decideDeadlineChange(
        DesignRequestDeadlineChange $change,
        User $marketing,
        bool $approve,
        ?string $reason = null,
    ): DesignRequestDeadlineChange {
        return DB::transaction(function () use ($change, $marketing, $approve, $reason) {
            $change = DesignRequestDeadlineChange::query()->whereKey($change->id)->lockForUpdate()->firstOrFail();
            $request = DesignRequest::query()->whereKey($change->design_request_id)->lockForUpdate()->firstOrFail();
            $this->assertMarketingOwner($request->marketingBrief, $marketing);
            if ($change->status !== 'PENDING') {
                throw ValidationException::withMessages(['decision' => 'Permintaan deadline sudah diputuskan.']);
            }
            if (in_array($request->status, ['COMPLETED', 'CANCELLED', 'CANCELLATION_PENDING_ACK'], true)) {
                throw ValidationException::withMessages(['decision' => 'Design Request tidak lagi menerima perubahan deadline.']);
            }
            $before = ['status' => $change->status, 'deadline_at' => $request->deadline_at?->toISOString()];
            $change->status = $approve ? 'APPROVED' : 'REJECTED';
            $change->decided_by = $marketing->id;
            $change->decided_at = now();
            $change->decision_reason = $reason;
            $change->save();
            if ($approve) {
                $request->deadline_at = $change->requested_deadline_at;
                $request->deadline_reminder_sent_at = null;
                $request->overdue_notified_at = null;
                $request->save();
            }
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $marketing,
                'MARKETING',
                'DEADLINE_CHANGE_DECIDED',
                $before,
                [
                    'status' => $change->status,
                    'deadline_at' => $request->deadline_at?->toISOString(),
                    'requested_deadline_at' => $change->requested_deadline_at->toISOString(),
                    'request_reason' => $change->request_reason,
                    'decision_reason' => $reason,
                ],
                $reason,
            );
            $this->notify($request->currentPic, 'DEADLINE_CHANGE_DECIDED', "Permintaan perubahan deadline Design Request #{$request->id} {$change->status}.", ['design_request_id' => $request->id]);
            $this->refreshBrief($request, $marketing);

            return $change;
        });
    }

    public function cancel(
        DesignRequest $designRequest,
        User $marketing,
        string $reason,
        ?string $replacementInformation = null,
        ?string $replacementSource = null,
    ): DesignRequest {
        return DB::transaction(function () use ($designRequest, $marketing, $reason, $replacementInformation, $replacementSource) {
            $request = DesignRequest::query()->whereKey($designRequest->id)->lockForUpdate()->firstOrFail();
            $this->assertMarketingOwner($request->marketingBrief, $marketing);
            if (in_array($request->status, ['COMPLETED', 'CANCELLED', 'CANCELLATION_PENDING_ACK'], true)) {
                throw ValidationException::withMessages(['task' => 'Status tugas tidak dapat dibatalkan saat ini.']);
            }

            $before = [
                'status' => $request->status,
                'current_pic_id' => $request->current_pic_id,
                'replacement_information' => $request->replacement_information,
                'replacement_source' => $request->replacement_source,
            ];
            $event = 'DESIGN_CANCELLED';
            if ($request->status === 'AVAILABLE') {
                $request->status = 'CANCELLED';
                $request->cancellation_acknowledged_at = null;
            } else {
                $request->status = 'CANCELLATION_PENDING_ACK';
                $request->cancellation_requested_at = now();
                $event = 'CANCELLATION_REQUESTED';
            }
            $request->cancellation_reason = $reason;
            $request->replacement_information = $replacementInformation;
            $request->replacement_source = $replacementSource;
            $request->save();
            $this->closePendingDeadlineChanges($request, $marketing, 'Design Request dibatalkan.');
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $marketing,
                'MARKETING',
                $event,
                $before,
                [
                    'status' => $request->status,
                    'current_pic_id' => $request->current_pic_id,
                    'replacement_information' => $request->replacement_information,
                    'replacement_source' => $request->replacement_source,
                ],
                $reason,
            );
            if ($request->currentPic !== null) {
                $this->notify($request->currentPic, 'CANCELLATION_REQUESTED', "Instruksi penghentian Design Request #{$request->id}; pekerjaan harus dihentikan.", ['design_request_id' => $request->id]);
            }
            $this->refreshBrief($request, $marketing);

            return $request;
        });
    }

    public function acknowledgeCancellation(DesignRequest $designRequest, User $designer): DesignRequest
    {
        return DB::transaction(function () use ($designRequest, $designer) {
            $request = DesignRequest::query()->whereKey($designRequest->id)->lockForUpdate()->firstOrFail();
            $this->assertAssignedDesigner($request, $designer);
            if ($request->status !== 'CANCELLATION_PENDING_ACK') {
                throw ValidationException::withMessages(['task' => 'Tidak ada instruksi pembatalan yang menunggu konfirmasi.']);
            }
            $before = ['status' => $request->status];
            $request->status = 'CANCELLED';
            $request->cancellation_acknowledged_at = now();
            $request->save();
            $this->activeAssignment($request, $designer)->update([
                'released_at' => now(),
                'release_reason' => $request->cancellation_reason,
            ]);
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $designer,
                'DESIGNER',
                'CANCELLATION_ACKNOWLEDGED',
                $before,
                ['status' => $request->status, 'acknowledged_at' => $request->cancellation_acknowledged_at->toISOString()],
            );
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $designer,
                'DESIGNER',
                'DESIGN_CANCELLED',
                ['status' => 'CANCELLATION_PENDING_ACK'],
                ['status' => 'CANCELLED'],
                $request->cancellation_reason,
            );
            $this->notify($request->marketingBrief->marketing, 'DESIGN_CANCELLED', "Instruksi pembatalan Design Request #{$request->id} telah diterima.", ['design_request_id' => $request->id]);
            $this->refreshBrief($request, $designer);

            return $request;
        });
    }

    private function refreshBrief(DesignRequest $request, User $actor): void
    {
        $brief = MarketingBrief::query()->whereKey($request->marketing_brief_id)->lockForUpdate()->firstOrFail();
        if ($brief->status === 'SUBMITTED') {
            return;
        }
        $result = $this->readiness->evaluate($brief);
        $wasReady = in_array($brief->status, ['READY_FOR_SUBMISSION', 'READY_WITH_NOTES'], true);
        $previousStatus = $brief->status;
        $previousSnapshot = $brief->readiness_snapshot;
        if ($wasReady) {
            $brief->status = $result['status'];
        }
        $brief->readiness_snapshot = $result;
        $brief->save();
        if ($wasReady && $result['ready'] && (
            $previousStatus !== $brief->status
            || $previousSnapshot !== $brief->readiness_snapshot
        )) {
            $this->audit->record(
                $brief,
                $request,
                $actor,
                $this->roleFor($actor),
                'BRIEF_READY',
                null,
                $result,
            );
        }
    }

    private function assertAssignedDesigner(DesignRequest $request, User $designer): void
    {
        abort_unless($request->current_pic_id === $designer->id, 403, 'Hanya PIC aktif yang dapat menjalankan aksi ini.');
    }

    private function assertMarketingOwner(MarketingBrief $brief, User $marketing): void
    {
        abort_unless($brief->marketing_user_id === $marketing->id, 403);
    }

    private function assertWorkCanContinue(DesignRequest $request): void
    {
        if (in_array($request->status, ['COMPLETED', 'CANCELLED', 'CANCELLATION_PENDING_ACK'], true)) {
            throw ValidationException::withMessages(['task' => 'Tugas terminal atau menunggu konfirmasi pembatalan tidak dapat diubah.']);
        }
    }

    private function activeAssignment(DesignRequest $request, User $designer): DesignRequestAssignment
    {
        return $request->assignments()
            ->where('designer_user_id', $designer->id)
            ->whereNull('released_at')
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function notify(?User $user, string $event, string $message, array $details = []): void
    {
        $user?->notify(new ProductionWorkflowNotification($event, $message, $details));
    }

    private function notifyMarketing(DesignRequest $request, string $event, string $message): void
    {
        $this->notify($request->marketingBrief->marketing, $event, $message, ['design_request_id' => $request->id]);
    }

    private function closePendingDeadlineChanges(DesignRequest $request, User $actor, string $reason): void
    {
        $pendingChanges = $request->deadlineChanges()->where('status', 'PENDING')->lockForUpdate()->get();
        foreach ($pendingChanges as $change) {
            $change->status = 'REJECTED';
            $change->decided_by = $actor->id;
            $change->decided_at = now();
            $change->decision_reason = $reason;
            $change->save();
            $this->audit->record(
                $request->marketingBrief,
                $request,
                $actor,
                'MARKETING',
                'DEADLINE_CHANGE_DECIDED',
                ['status' => 'PENDING', 'deadline_at' => $request->deadline_at?->toISOString()],
                ['status' => 'REJECTED', 'deadline_at' => $request->deadline_at?->toISOString()],
                $reason,
            );
        }
    }

    private function roleFor(User $user): string
    {
        return $user->roles()->where('code', 'MARKETING')->exists() ? 'MARKETING' : 'DESIGNER';
    }
}
