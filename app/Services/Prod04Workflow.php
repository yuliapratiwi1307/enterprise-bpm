<?php

namespace App\Services;

use App\Models\MarketingBrief;
use App\Models\Prod04Acknowledgement;
use App\Models\Prod04BriefVersion;
use App\Models\Prod04DeliveryAttempt;
use App\Models\Prod04DistributionRecipient;
use App\Models\Prod04DocumentAccess;
use App\Models\Prod04Event;
use App\Models\Prod04SlaTracking;
use App\Models\Prod04ValidationTask;
use App\Models\User;
use App\Notifications\ProductionWorkflowNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Prod04Workflow
{
    private const READY_STATUSES = ['READY_FOR_SUBMISSION', 'READY_WITH_NOTES'];

    public function __construct(
        private readonly BpmRoleAuthorization $roles,
        private readonly MarketingBriefReadiness $readiness,
        private readonly Prod04WorkingCalendar $calendar,
        private readonly Prod04RecipientAccess $recipientAccess,
    ) {}

    public function submit(MarketingBrief $marketingBrief, User $actor): Prod04BriefVersion
    {
        $this->roles->require($actor, 'MARKETING');

        return DB::transaction(function () use ($marketingBrief, $actor): Prod04BriefVersion {
            $brief = MarketingBrief::query()
                ->whereKey($marketingBrief->id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($brief->marketing_user_id === $actor->id, 403);

            $existingVersion = $brief->prod04Versions()->latest('revision')->first();
            if ($existingVersion !== null) {
                return $existingVersion;
            }

            $this->calendar->configuration();
            $result = $this->readiness->evaluate($brief);
            if (
                ! $result['ready']
                || ! in_array($brief->status, self::READY_STATUSES, true)
                || ! in_array($result['status'], self::READY_STATUSES, true)
            ) {
                throw ValidationException::withMessages([
                    'brief' => 'Brief Marketing harus memenuhi readiness PROD-03 sebelum Submit & Distribute.',
                ]);
            }

            $recipients = $this->resolveRecipients();
            $year = now($this->calendar->configuration()['timezone'])->year;
            $documentNumber = $this->documentNumber($brief, $year);
            $revision = (int) $brief->prod04Versions()->max('revision') + 1;
            $snapshot = $this->snapshot($brief, $documentNumber, $revision, $result, $recipients);

            $version = Prod04BriefVersion::query()->create([
                'marketing_brief_id' => $brief->id,
                'document_number' => $documentNumber,
                'revision' => $revision,
                'readiness_status' => $result['status'],
                'snapshot' => $snapshot,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'distribution_status' => 'PENDING_DELIVERY',
            ]);

            foreach ($recipients as $recipient) {
                Prod04DistributionRecipient::query()->create([
                    'brief_version_id' => $version->id,
                    'user_id' => $recipient['user']->id,
                    'role_code' => $recipient['role'],
                    'obligation_type' => $recipient['obligation'],
                ]);
            }

            $before = ['status' => $brief->status];
            $brief->status = 'SUBMITTED';
            $brief->submitted_at = now();
            $brief->save();

            $this->record($brief, $version, $actor, 'MARKETING', 'BRIEF_SUBMITTED', $before, [
                'document_number' => $documentNumber,
                'revision' => $revision,
                'readiness_status' => $result['status'],
            ]);
            $this->record($brief, $version, $actor, 'BPM', 'DISTRIBUTION_STARTED', null, [
                'recipient_ids' => $version->recipients()->pluck('user_id')->all(),
            ]);

            return $version;
        });
    }

    public function distribute(Prod04BriefVersion $version, User $actor): Prod04BriefVersion
    {
        $isMarketingOwner = $this->roles->has($actor, 'MARKETING')
            && $version->marketingBrief()->where('marketing_user_id', $actor->id)->exists();
        abort_unless($isMarketingOwner || $this->roles->has($actor, 'ADMIN_BPM'), 403);

        $this->calendar->configuration();
        $version = Prod04BriefVersion::query()->whereKey($version->id)->firstOrFail();
        if ($version->distribution_status === 'PENDING_ACKNOWLEDGEMENT' || $version->distribution_status === 'ACKNOWLEDGED') {
            $version->recipients()
                ->with('user')
                ->where('delivery_status', 'DELIVERED')
                ->each(fn (Prod04DistributionRecipient $recipient) => $this->notifyDeliveredRecipient($recipient));

            return $version;
        }

        DB::transaction(function () use ($version, $actor): void {
            $locked = Prod04BriefVersion::query()
                ->with('marketingBrief')
                ->whereKey($version->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($locked->distribution_status === 'PENDING_ACKNOWLEDGEMENT' || $locked->distribution_status === 'ACKNOWLEDGED') {
                return;
            }
            if ($locked->delivery_retrying) {
                throw ValidationException::withMessages([
                    'distribution' => 'Retry distribusi untuk versi ini sedang berjalan.',
                ]);
            }
            if ($locked->distribution_status === 'DELIVERY_FAILED') {
                $this->record(
                    $locked->marketingBrief,
                    $locked,
                    $actor,
                    $this->roles->has($actor, 'ADMIN_BPM') ? 'ADMIN_BPM' : 'MARKETING',
                    'DELIVERY_RETRIED',
                    ['distribution_status' => 'DELIVERY_FAILED'],
                    ['recipient_ids' => $locked->recipients()->pluck('id')->all()],
                );
            }
            $locked->delivery_retrying = true;
            $locked->save();
        });

        try {
            $this->restoreManifestRecipients($version);
            $recipients = $version->recipients()->with('user')->orderBy('id')->get();

            foreach ($recipients as $recipient) {
                if ($recipient->delivery_status === 'DELIVERED') {
                    $this->notifyDeliveredRecipient($recipient);

                    continue;
                }

                $this->deliverToRecipient($version, $recipient, $actor);
            }
        } finally {
            Prod04BriefVersion::query()->whereKey($version->id)->update(['delivery_retrying' => false]);
        }

        return DB::transaction(function () use ($version, $actor): Prod04BriefVersion {
            $locked = Prod04BriefVersion::query()
                ->with(['marketingBrief', 'recipients', 'validationTask', 'slaTracking', 'acknowledgement'])
                ->whereKey($version->id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked->delivery_retrying = false;
            $manifest = collect($locked->snapshot['distribution_manifest'] ?? []);
            $recipientMap = $locked->recipients->keyBy('user_id');
            $allRecipientsDelivered = $manifest->isNotEmpty()
                && $manifest->count() === $locked->recipients->count()
                && $manifest->every(function (array $expected) use ($recipientMap): bool {
                    $actual = $recipientMap->get($expected['user_id']);

                    return $actual !== null
                        && $actual->obligation_type === $expected['obligation_type']
                        && $actual->delivery_status === 'DELIVERED';
                });
            $primaryRecipient = $locked->recipients->firstWhere('obligation_type', 'PRIMARY');
            $taskAvailable = $locked->validationTask !== null;
            $previousStatus = $locked->distribution_status;

            if ($allRecipientsDelivered && $taskAvailable && $primaryRecipient !== null) {
                $availableAt = now();
                $locked->distribution_status = $locked->acknowledgement === null
                    ? 'PENDING_ACKNOWLEDGEMENT'
                    : 'ACKNOWLEDGED';
                $locked->delivered_at ??= $availableAt;
                $locked->save();

                $tracking = Prod04SlaTracking::query()->firstOrCreate(
                    ['brief_version_id' => $locked->id],
                    ['available_at' => $locked->delivered_at],
                );

                if ($previousStatus !== $locked->distribution_status) {
                    $this->record($locked->marketingBrief, $locked, $actor, 'BPM', 'DOCUMENT_DELIVERED', [
                        'distribution_status' => $previousStatus,
                    ], [
                        'distribution_status' => $locked->distribution_status,
                        'available_at' => $tracking->available_at->toISOString(),
                    ]);
                    $this->record($locked->marketingBrief, $locked, $actor, 'BPM', 'BRIEF_MARKETING_DELIVERED_TO_ASST_MANAGER', null, [
                        'primary_recipient_id' => $primaryRecipient->user_id,
                        'available_at' => $tracking->available_at->toISOString(),
                    ]);
                    $this->record($locked->marketingBrief, $locked, $actor, 'BPM', 'HANDOVER_ACTIVATED', null, [
                        'task_id' => $locked->validationTask->id,
                        'recipient_user_id' => $primaryRecipient->user_id,
                    ]);
                    if ($previousStatus === 'DELIVERY_FAILED') {
                        $this->notify(
                            $locked->marketingBrief->marketing,
                            $locked,
                            'DELIVERY_RECOVERED',
                            'Pemulihan distribusi Brief Marketing berhasil; seluruh penerima dan tugas validasi tersedia.',
                        );
                        $this->notifyRecipients($locked, ['PRIMARY', 'COPY'], 'DELIVERY_RECOVERED', 'Distribusi ulang Brief Marketing berhasil.');
                    }
                }
            } else {
                $locked->distribution_status = 'DELIVERY_FAILED';
                $locked->save();
                if ($previousStatus !== 'DELIVERY_FAILED') {
                    $this->record($locked->marketingBrief, $locked, $actor, 'BPM', 'DELIVERY_FAILED', [
                        'distribution_status' => $previousStatus,
                    ], [
                        'recipient_statuses' => $locked->recipients->pluck('delivery_status', 'id')->all(),
                    ]);
                    $this->notifyDeliveryFailure($locked);
                }
            }

            return $locked;
        });
    }

    public function acknowledge(Prod04BriefVersion $version, User $actor, string $method = 'MANUAL'): Prod04Acknowledgement
    {
        if (! in_array($method, ['MANUAL', 'AUTOMATIC'], true)) {
            throw ValidationException::withMessages(['acknowledgement' => 'Metode Acknowledge tidak valid.']);
        }

        return DB::transaction(function () use ($version, $actor, $method): Prod04Acknowledgement {
            $locked = Prod04BriefVersion::query()
                ->with(['marketingBrief', 'validationTask'])
                ->whereKey($version->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->roles->require($actor, 'ASST_MANAGER_PRODUKSI');
            abort_unless(
                $locked->recipients()->where('user_id', $actor->id)->where('obligation_type', 'PRIMARY')->exists(),
                403,
            );
            $primaryDelivered = $locked->recipients()
                ->where('user_id', $actor->id)
                ->where('obligation_type', 'PRIMARY')
                ->where('delivery_status', 'DELIVERED')
                ->exists();
            if (
                ! $primaryDelivered
                || $locked->validationTask === null
                || ! in_array($locked->distribution_status, ['PENDING_ACKNOWLEDGEMENT', 'ACKNOWLEDGED', 'DELIVERY_FAILED'], true)
            ) {
                throw ValidationException::withMessages([
                    'acknowledgement' => 'Dokumen dan tugas validasi belum tersedia untuk penerima utama.',
                ]);
            }

            $acknowledgement = Prod04Acknowledgement::query()->firstOrCreate(
                ['brief_version_id' => $locked->id],
                [
                    'acknowledged_by' => $actor->id,
                    'method' => $method,
                    'acknowledged_at' => now(),
                ],
            );

            if ($acknowledgement->wasRecentlyCreated) {
                $before = ['distribution_status' => $locked->distribution_status];
                if ($locked->distribution_status !== 'DELIVERY_FAILED') {
                    $locked->distribution_status = 'ACKNOWLEDGED';
                }
                $locked->save();
                $this->record($locked->marketingBrief, $locked, $actor, 'ASST_MANAGER_PRODUKSI', 'ACKNOWLEDGEMENT_COMPLETED', $before, [
                    'distribution_status' => $locked->distribution_status,
                    'method' => $method,
                    'acknowledged_at' => $acknowledgement->acknowledged_at->toISOString(),
                ]);
                $this->notify(
                    $locked->marketingBrief->marketing,
                    $locked,
                    'ACKNOWLEDGEMENT_COMPLETED',
                    'Asst Manager Produksi telah mengonfirmasi penerimaan Brief Marketing.',
                );
            }

            return $acknowledgement;
        });
    }

    public function acknowledgeForValidationStart(Prod04ValidationTask $task, User $actor): Prod04Acknowledgement
    {
        $task->loadMissing('briefVersion');

        return $this->acknowledge($task->briefVersion, $actor, 'AUTOMATIC');
    }

    public function processSla(): void
    {
        $this->calendar->configuration();

        Prod04SlaTracking::query()
            ->with(['briefVersion.marketingBrief.marketing', 'briefVersion.recipients.user', 'briefVersion.acknowledgement'])
            ->whereHas('briefVersion', fn ($query) => $query->where('distribution_status', 'PENDING_ACKNOWLEDGEMENT'))
            ->orderBy('id')
            ->chunkById(100, function (Collection $trackings): void {
                foreach ($trackings as $tracking) {
                    DB::transaction(function () use ($tracking): void {
                        $locked = Prod04SlaTracking::query()
                            ->with(['briefVersion.marketingBrief.marketing', 'briefVersion.recipients.user', 'briefVersion.acknowledgement'])
                            ->whereKey($tracking->id)
                            ->lockForUpdate()
                            ->firstOrFail();
                        $version = $locked->briefVersion;

                        if ($version->acknowledgement !== null) {
                            return;
                        }

                        $elapsedMinutes = $this->calendar->workingMinutesBetween($locked->available_at, now());
                        if ($elapsedMinutes >= 180 && $locked->reminder_sent_at === null) {
                            $locked->reminder_sent_at = now();
                            $locked->save();
                            $this->record($version->marketingBrief, $version, null, 'BPM', 'REMINDER_SENT', null, [
                                'recipient_role' => 'ASST_MANAGER_PRODUKSI',
                                'elapsed_working_minutes' => $elapsedMinutes,
                            ]);
                            $this->notifyRecipients($version, ['PRIMARY'], 'ACK_REMINDER', 'Pengingat: Acknowledge Brief Marketing diperlukan pada jam kerja ke-3.');
                        }

                        if ($elapsedMinutes >= 240 && $locked->overdue_detected_at === null) {
                            $locked->overdue_detected_at = now();
                            $locked->save();
                            $this->record($version->marketingBrief, $version, null, 'BPM', 'ACK_OVERDUE_DETECTED', null, [
                                'elapsed_working_minutes' => $elapsedMinutes,
                            ]);
                            $this->notifyRecipients($version, ['PRIMARY'], 'ACK_OVERDUE_DETECTED', 'Acknowledge Brief Marketing telah melewati SLA 4 jam kerja.');
                            $this->notify($version->marketingBrief->marketing, $version, 'ACK_OVERDUE_DETECTED', 'Acknowledge Brief Marketing telah melewati SLA 4 jam kerja.');

                            return;
                        }

                    });
                }
            });
    }

    public function recordDocumentViewed(Prod04BriefVersion $version, User $actor): void
    {
        $access = Prod04DocumentAccess::query()
            ->where('brief_version_id', $version->id)
            ->where('user_id', $actor->id)
            ->firstOrFail();

        DB::transaction(function () use ($version, $actor, $access): void {
            $access->forceFill(['viewed_at' => now()])->save();
            $this->record(
                $version->marketingBrief,
                $version,
                $actor,
                $this->roleFor($actor),
                'DOCUMENT_VIEWED',
                null,
                ['user_id' => $actor->id, 'viewed_at' => now()->toISOString()],
            );
        });
    }

    /**
     * @return Collection<int, array{user: User, role: string, obligation: string}>
     */
    private function resolveRecipients(): Collection
    {
        $roles = config('prod04.recipient_roles');
        $primaryCode = $roles['primary'] ?? null;
        $copyCode = $roles['copy'] ?? null;

        if (! is_string($primaryCode) || ! is_string($copyCode)) {
            throw ValidationException::withMessages([
                'distribution' => 'Pemetaan role penerima PROD-04 belum dikonfigurasi.',
            ]);
        }

        $primaryUsers = $this->usersForRole($primaryCode);
        $copyUsers = $this->usersForRole($copyCode);

        if ($primaryUsers->count() !== 1 || $copyUsers->isEmpty()) {
            throw ValidationException::withMessages([
                'distribution' => 'Distribusi memerlukan tepat satu Asst Manager Produksi dan minimal satu Admin Produksi yang aktif.',
            ]);
        }

        if ($copyUsers->contains(fn (User $user): bool => $user->is($primaryUsers->first()))) {
            throw ValidationException::withMessages([
                'distribution' => 'Penerima utama dan penerima salinan harus memiliki identitas terpisah.',
            ]);
        }

        return collect([
            ['user' => $primaryUsers->first(), 'role' => $primaryCode, 'obligation' => 'PRIMARY'],
            ...$copyUsers
                ->map(fn (User $user): array => ['user' => $user, 'role' => $copyCode, 'obligation' => 'COPY'])
                ->all(),
        ]);
    }

    private function usersForRole(string $code): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('code', $code))
            ->orderBy('users.id')
            ->get();
    }

    private function deliverToRecipient(
        Prod04BriefVersion $version,
        Prod04DistributionRecipient $recipient,
        User $actor,
    ): void {
        try {
            DB::transaction(function () use ($version, $recipient, $actor): void {
                $lockedRecipient = Prod04DistributionRecipient::query()
                    ->whereKey($recipient->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($lockedRecipient->delivery_status === 'DELIVERED') {
                    return;
                }

                $attemptNumber = $lockedRecipient->attempt_count + 1;
                $now = now();
                $taskAlreadyExisted = $lockedRecipient->obligation_type === 'PRIMARY'
                    && Prod04ValidationTask::query()->where('brief_version_id', $version->id)->exists();
                $this->recipientAccess->grant($version, $lockedRecipient, $now);
                if ($lockedRecipient->obligation_type === 'PRIMARY' && ! $taskAlreadyExisted) {
                    $this->record($version->marketingBrief, $version, $actor, 'BPM', 'TASK_ACTIVATED', null, [
                        'version_id' => $version->id,
                        'recipient_user_id' => $lockedRecipient->user_id,
                        'task_status' => 'AVAILABLE',
                    ]);
                }

                $lockedRecipient->delivery_status = 'DELIVERED';
                $lockedRecipient->attempt_count = $attemptNumber;
                $lockedRecipient->last_error = null;
                $lockedRecipient->delivered_at ??= $now;
                $lockedRecipient->save();
                Prod04DeliveryAttempt::query()->create([
                    'brief_version_id' => $version->id,
                    'recipient_id' => $lockedRecipient->id,
                    'attempt_number' => $attemptNumber,
                    'result' => 'DELIVERED',
                    'attempted_at' => $now,
                ]);
                $this->record($version->marketingBrief, $version, $actor, 'BPM', 'DOCUMENT_DELIVERED', null, [
                    'recipient_id' => $lockedRecipient->id,
                    'recipient_user_id' => $lockedRecipient->user_id,
                    'obligation_type' => $lockedRecipient->obligation_type,
                ]);
            });
        } catch (QueryException $exception) {
            DB::transaction(function () use ($version, $recipient, $actor, $exception): void {
                $lockedRecipient = Prod04DistributionRecipient::query()
                    ->whereKey($recipient->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($lockedRecipient->delivery_status === 'DELIVERED') {
                    return;
                }

                $attemptNumber = $lockedRecipient->attempt_count + 1;
                $occurredAt = now();
                $sqlState = $exception->errorInfo[0] ?? 'UNKNOWN';
                $error = "Database delivery operation failed (SQLSTATE {$sqlState}).";
                $lockedRecipient->delivery_status = 'DELIVERY_FAILED';
                $lockedRecipient->attempt_count = $attemptNumber;
                $lockedRecipient->last_error = $error;
                $lockedRecipient->save();
                Prod04DeliveryAttempt::query()->create([
                    'brief_version_id' => $version->id,
                    'recipient_id' => $lockedRecipient->id,
                    'attempt_number' => $attemptNumber,
                    'result' => 'DELIVERY_FAILED',
                    'error' => $error,
                    'attempted_at' => $occurredAt,
                ]);
                $this->record($version->marketingBrief, $version, $actor, 'BPM', 'DELIVERY_FAILED', null, [
                    'recipient_id' => $lockedRecipient->id,
                    'recipient_user_id' => $lockedRecipient->user_id,
                    'error' => $error,
                ]);
            });
        }

        $recipient->refresh()->load('user');
        $this->notifyDeliveredRecipient($recipient);
    }

    private function notifyDeliveredRecipient(Prod04DistributionRecipient $recipient): void
    {
        if ($recipient->delivery_status !== 'DELIVERED' || $recipient->notified_at !== null) {
            return;
        }

        try {
            DB::transaction(function () use ($recipient): void {
                $locked = Prod04DistributionRecipient::query()
                    ->whereKey($recipient->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($locked->notified_at !== null || $locked->delivery_status !== 'DELIVERED') {
                    return;
                }

                $locked->user->notify(new ProductionWorkflowNotification(
                    $locked->obligation_type === 'PRIMARY' ? 'BRIEF_DELIVERED' : 'BRIEF_COPY_DELIVERED',
                    "Brief Marketing {$locked->briefVersion->document_number} Rev.".str_pad((string) $locked->briefVersion->revision, 2, '0', STR_PAD_LEFT).' tersedia.',
                    [
                        'brief_version_id' => $locked->brief_version_id,
                        'document_number' => $locked->briefVersion->document_number,
                        'revision' => $locked->briefVersion->revision,
                        'obligation_type' => $locked->obligation_type,
                    ],
                ));
                $locked->notified_at = now();
                $locked->save();
            });
        } catch (QueryException $exception) {
            Log::error('PROD-04 internal delivery notification failed.', [
                'brief_version_id' => $recipient->brief_version_id,
                'recipient_id' => $recipient->id,
                'exception' => $exception,
            ]);
        }
    }

    private function documentNumber(MarketingBrief $brief, int $year): string
    {
        $existing = $brief->prod04Versions()->value('document_number');
        if ($existing !== null) {
            return $existing;
        }

        $row = DB::selectOne(
            <<<'SQL'
                INSERT INTO prod04_document_sequences (year, last_number, created_at, updated_at)
                VALUES (?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT (year) DO UPDATE
                SET last_number = prod04_document_sequences.last_number + 1,
                    updated_at = CURRENT_TIMESTAMP
                RETURNING last_number
                SQL,
            [$year],
        );

        return sprintf('BM-%04d-%04d', $year, (int) $row->last_number);
    }

    private function restoreManifestRecipients(Prod04BriefVersion $version): void
    {
        foreach ($version->snapshot['distribution_manifest'] ?? [] as $recipient) {
            Prod04DistributionRecipient::query()->firstOrCreate(
                [
                    'brief_version_id' => $version->id,
                    'user_id' => $recipient['user_id'],
                ],
                [
                    'role_code' => $recipient['role_code'],
                    'obligation_type' => $recipient['obligation_type'],
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $readiness
     * @return array<string, mixed>
     */
    private function snapshot(
        MarketingBrief $brief,
        string $documentNumber,
        int $revision,
        array $readiness,
        Collection $recipients,
    ): array {
        $brief->loadMissing(['clientBrief', 'components', 'designRequests']);

        return [
            'document_number' => $documentNumber,
            'revision' => $revision,
            'submitted_at' => now()->toISOString(),
            'distribution_manifest' => $recipients->map(fn (array $recipient): array => [
                'user_id' => $recipient['user']->id,
                'role_code' => $recipient['role'],
                'obligation_type' => $recipient['obligation'],
            ])->values()->all(),
            'client_request' => [
                'id' => $brief->clientBrief->id,
                'request_number' => $brief->clientBrief->request_number,
                'client_name' => $brief->clientBrief->client_name,
            ],
            'marketing_brief' => [
                'id' => $brief->id,
                'readiness_status' => $readiness['status'],
                'readiness_snapshot' => $readiness,
                'components' => $brief->components
                    ->sortBy('component')
                    ->map(fn ($component): array => [
                        'component' => $component->component,
                        'value' => $component->value,
                        'is_provisional' => $component->is_provisional,
                        'information_basis' => $component->information_basis,
                    ])
                    ->values()
                    ->all(),
            ],
            'design_requests' => $brief->designRequests
                ->sortBy('id')
                ->map(fn ($request): array => [
                    'id' => $request->id,
                    'title' => $request->title,
                    'description' => $request->description,
                    'classification' => $request->classification,
                    'status' => $request->status,
                    'impact' => $request->impact,
                    'deadline_at' => $request->deadline_at?->toISOString(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function record(
        MarketingBrief $brief,
        ?Prod04BriefVersion $version,
        ?User $actor,
        string $role,
        string $event,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?Prod04DistributionRecipient $recipient = null,
    ): void {
        Prod04Event::query()->create([
            'marketing_brief_id' => $brief->id,
            'brief_version_id' => $version?->id,
            'recipient_id' => $recipient?->id,
            'actor_user_id' => $actor?->id,
            'actor_role' => $role,
            'event_type' => $event,
            'before_data' => $before,
            'after_data' => $after,
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
    }

    private function notify(User $user, Prod04BriefVersion $version, string $event, string $message): void
    {
        $user->notify(new ProductionWorkflowNotification($event, $message, [
            'brief_version_id' => $version->id,
            'document_number' => $version->document_number,
            'revision' => $version->revision,
        ]));
    }

    private function notifyDeliveryFailure(Prod04BriefVersion $version): void
    {
        $marketing = $version->marketingBrief->marketing;
        $this->notifySafely(
            $marketing,
            $version,
            'Distribusi Brief Marketing gagal atau belum lengkap; Marketing dapat mencoba retry.',
        );

        $administratorRole = config('prod04.recipient_roles.bpm_administrator');
        if (! is_string($administratorRole) || $administratorRole === '') {
            Log::critical('PROD-04 delivery failure has no configured BPM administrator recipient.', [
                'brief_version_id' => $version->id,
            ]);

            return;
        }

        $administrators = $this->usersForRole($administratorRole);
        if ($administrators->isEmpty()) {
            Log::critical('PROD-04 delivery failure has no BPM administrator accounts.', [
                'brief_version_id' => $version->id,
                'role_code' => $administratorRole,
            ]);

            return;
        }

        foreach ($administrators as $administrator) {
            $this->notifySafely(
                $administrator,
                $version,
                'Distribusi Brief Marketing gagal atau belum lengkap; penanganan teknis diperlukan.',
            );
        }
    }

    private function notifySafely(User $user, Prod04BriefVersion $version, string $message): void
    {
        try {
            $this->notify($user, $version, 'DELIVERY_FAILED', $message);
        } catch (QueryException $exception) {
            Log::critical('PROD-04 failure notification could not be persisted.', [
                'brief_version_id' => $version->id,
                'recipient_user_id' => $user->id,
                'exception' => $exception,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $obligations
     */
    private function notifyRecipients(Prod04BriefVersion $version, array $obligations, string $event, string $message): void
    {
        $version->recipients()
            ->with('user')
            ->whereIn('obligation_type', $obligations)
            ->each(fn (Prod04DistributionRecipient $recipient) => $this->notify($recipient->user, $version, $event, $message));
    }

    private function roleFor(User $user): string
    {
        return $user->roles()->value('code') ?? 'UNKNOWN';
    }
}
