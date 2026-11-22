<?php

namespace App\Services;

use App\Models\Prod04BriefVersion;
use App\Models\Prod05ActivityControl;
use App\Models\Prod05RevisionClassification;
use App\Models\Prod05Validation;
use App\Models\Prod05ValidationEvent;
use App\Models\User;
use App\Notifications\ProductionWorkflowNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Prod05ValidationService
{
    public const STATUSES = [
        'DRAFT',
        'UNDER_REVIEW',
        'RETURNED',
        'VALIDATED',
        'VALIDATED_WITH_NOTES',
        'PENDING_MANAGER',
        'APPROVED_FOR_REVIEW',
        'APPROVED_CONDITIONAL',
        'ON_HOLD',
    ];

    public const IMPACT_CLASSIFICATIONS = [
        'NO_IMPACT',
        'MINOR',
        'MAJOR',
        'CRITICAL',
        'UNDETERMINED',
    ];

    public const MANAGER_DECISIONS = [
        'APPROVED_FOR_REVIEW',
        'APPROVED_CONDITIONAL',
        'REJECTED',
    ];

    public const ACTIVITY_CONTROL_STATUSES = [
        'ACTIVE',
        'ON_HOLD',
        'RELEASED',
    ];

    public const LEVEL_3_ACTIVITY_TYPES = [
        'PHYSICAL_PRODUCTION',
        'IRREVERSIBLE_PURCHASE',
    ];

    public function __construct(
        private readonly BpmRoleAuthorization $roles,
        private readonly Prod04Workflow $prod04Workflow,
    ) {}

    /**
     * Start formal validation on a PROD-04 brief version.
     * Only ASST_MANAGER_PRODUKSI may start validation.
     * Invokes acknowledgeForValidationStart() if acknowledgement is pending.
     */
    public function startValidation(Prod04BriefVersion $version, User $actor): Prod05Validation
    {
        $this->roles->require($actor, 'ASST_MANAGER_PRODUKSI');

        return DB::transaction(function () use ($version, $actor): Prod05Validation {
            $locked = Prod04BriefVersion::query()
                ->with(['validationTask', 'acknowledgement', 'marketingBrief'])
                ->whereKey($version->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->validationTask === null) {
                throw ValidationException::withMessages([
                    'validation' => 'Tugas validasi belum tersedia untuk versi ini.',
                ]);
            }

            if (! in_array($locked->distribution_status, ['PENDING_ACKNOWLEDGEMENT', 'ACKNOWLEDGED'], true)) {
                throw ValidationException::withMessages([
                    'validation' => 'Distribusi belum tersedia untuk validasi.',
                ]);
            }

            $existing = Prod05Validation::query()->where('brief_version_id', $locked->id)->first();
            if ($existing !== null) {
                return $existing;
            }

            if ($locked->acknowledgement === null) {
                $this->prod04Workflow->acknowledgeForValidationStart($locked->validationTask, $actor);
            }

            $validation = Prod05Validation::query()->create([
                'brief_version_id' => $locked->id,
                'validated_by' => $actor->id,
                'status' => 'UNDER_REVIEW',
            ]);

            $this->recordEvent($validation, $actor, 'ASST_MANAGER_PRODUKSI', 'VALIDATION_STARTED', null, [
                'status' => 'UNDER_REVIEW',
                'brief_version_id' => $locked->id,
                'revision' => $locked->revision,
            ]);

            return $validation;
        });
    }

    /**
     * Give validation result on an UNDER_REVIEW validation.
     * Only ASST_MANAGER_PRODUKSI may give validation results.
     */
    public function giveValidationResult(
        Prod05Validation $validation,
        User $actor,
        string $status,
        array $data = []
    ): Prod05Validation {
        $this->roles->require($actor, 'ASST_MANAGER_PRODUKSI');

        return DB::transaction(function () use ($validation, $actor, $status, $data): Prod05Validation {
            $locked = Prod05Validation::query()
                ->with(['briefVersion.marketingBrief', 'events'])
                ->whereKey($validation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'UNDER_REVIEW') {
                throw ValidationException::withMessages([
                    'validation' => 'Validasi tidak dalam status UNDER_REVIEW.',
                ]);
            }

            if (! in_array($status, self::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status validasi tidak valid.',
                ]);
            }

            $before = ['status' => $locked->status];
            $after = ['status' => $status];

            $provisionalData = $data['provisional_data'] ?? null;
            $returnReason = $data['return_reason'] ?? null;
            $returnField = $data['return_field'] ?? null;

            $locked->status = $status;
            $locked->provisional_data = $provisionalData;
            $locked->return_reason = $returnReason;
            $locked->return_field = $returnField;

            if ($status === 'VALIDATED_WITH_NOTES' && $provisionalData !== null) {
                $after['provisional_data'] = $provisionalData;
            }
            if ($status === 'RETURNED') {
                $after['return_reason'] = $returnReason;
                $after['return_field'] = $returnField;
            }
            if ($status === 'PENDING_MANAGER') {
                $after['pending_manager_reason'] = $data['reason'] ?? null;
            }

            $locked->save();

            $this->recordEvent($locked, $actor, 'ASST_MANAGER_PRODUKSI', 'VALIDATION_RESULT_GIVEN', $before, $after, $data['reason'] ?? null);

            if ($status === 'VALIDATED_WITH_NOTES') {
                $this->notifyValidationResult($locked, 'VALIDATION_WITH_NOTES', 'Validasi selesai dengan catatan Provisional.');
            } elseif ($status === 'VALIDATED') {
                $this->notifyValidationResult($locked, 'VALIDATION_COMPLETED', 'Validasi selesai. Informasi memenuhi kriteria.');
            } elseif ($status === 'RETURNED') {
                $this->notifyValidationResult($locked, 'VALIDATION_RETURNED', 'Validasi dikembalikan untuk revisi.');
            } elseif ($status === 'PENDING_MANAGER') {
                $this->notifyValidationResult($locked, 'PENDING_MANAGER_DECISION', 'Memerlukan keputusan Manager Produksi.');
            }

            return $locked;
        });
    }

    /**
     * Return brief for revision with reasons and required corrections.
     */
    public function returnForRevision(
        Prod05Validation $validation,
        User $actor,
        string $reason,
        string $field
    ): Prod05Validation {
        return $this->giveValidationResult($validation, $actor, 'RETURNED', [
            'return_reason' => $reason,
            'return_field' => $field,
            'reason' => $reason,
        ]);
    }

    /**
     * Classify impact of a revision.
     * Asst Manager recommends classification; may override recommendation with reason.
     * Downgrading from CRITICAL requires Manager Produksi approval.
     */
    // F15D_HARDENED: classification downgrade requires explicit Manager decision.
    public function classifyImpact(
        Prod05Validation $validation, User $actor, string $classification,
        string $reason, bool $overrideRecommendation = false,
        ?array $affectedActivityKeys = null
    ): Prod05RevisionClassification {
        $this->roles->require($actor, 'ASST_MANAGER_PRODUKSI');

        return DB::transaction(function () use ($validation, $actor, $classification, $reason, $affectedActivityKeys) {
            $locked = Prod05Validation::query()->whereKey($validation->id)->lockForUpdate()->firstOrFail();
            if (! in_array($classification, self::IMPACT_CLASSIFICATIONS, true)) {
                throw ValidationException::withMessages(['classification' => 'Klasifikasi tidak valid.']);
            }
            $latest = $locked->revisionClassifications()->orderByDesc('id')->first();
            $previous = $latest?->confirmed_classification ?? $latest?->recommended_classification;
            if ($previous === 'CRITICAL' && $classification !== 'CRITICAL') {
                // Recommendation only. It must not alter the effective CRITICAL classification.
                abort(403, 'Penurunan CRITICAL memerlukan keputusan Manager Produksi.');
            }
            $record = Prod05RevisionClassification::query()->create([
                'validation_id' => $locked->id,
                'revision' => ($latest?->revision ?? 0) + 1,
                'recommended_by' => $actor->id,
                'recommended_classification' => $classification,
                'confirmed_by' => $actor->id,
                'confirmed_classification' => $classification,
                'manager_approval_for_downgrade' => false,
                'confirmation_reason' => $reason,
                'occurred_at' => now(),
            ]);
            $locked->impact_classification = $classification;
            $locked->impact_classification_confirmed = $classification;
            $locked->save();
            $this->recordEvent($locked, $actor, 'ASST_MANAGER_PRODUKSI', 'IMPACT_CLASSIFIED',
                ['previous_classification' => $previous], ['classification' => $classification, 'revision' => $record->revision], $reason);
            if ($classification === 'CRITICAL') {
                $this->applyCriticalHold($locked, $actor, $affectedActivityKeys);
            }

            return $record;
        });
    }

    /** Explicit Manager Produksi confirmation of a CRITICAL downgrade. */
    public function approveCriticalDowngrade(Prod05Validation $validation, User $actor, string $classification, string $reason): Prod05RevisionClassification
    {
        $this->roles->require($actor, 'MANAGER_PRODUKSI');
        if (! in_array($classification, ['NO_IMPACT', 'MINOR', 'MAJOR'], true) || trim($reason) === '') {
            throw ValidationException::withMessages(['classification' => 'Target downgrade atau alasan tidak valid.']);
        }

        return DB::transaction(function () use ($validation, $actor, $classification, $reason) {
            $locked = Prod05Validation::query()->whereKey($validation->id)->lockForUpdate()->firstOrFail();
            if ($locked->impact_classification !== 'CRITICAL') {
                throw ValidationException::withMessages(['classification' => 'Klasifikasi saat ini bukan CRITICAL.']);
            }
            $latest = $locked->revisionClassifications()->orderByDesc('id')->first();
            if ($latest === null || $latest->confirmed_classification !== 'CRITICAL') {
                throw ValidationException::withMessages(['classification' => 'Riwayat CRITICAL tidak ditemukan.']);
            }
            $record = Prod05RevisionClassification::query()->create([
                'validation_id' => $locked->id, 'revision' => $latest->revision + 1,
                'recommended_by' => $latest->recommended_by,
                'recommended_classification' => $classification,
                'confirmed_by' => $actor->id,
                'confirmed_classification' => $classification,
                'manager_approval_for_downgrade' => true,
                'confirmation_reason' => $reason, 'occurred_at' => now(),
            ]);
            $locked->impact_classification = $classification;
            $locked->impact_classification_confirmed = $classification;
            $locked->save();
            $this->recordEvent($locked, $actor, 'MANAGER_PRODUKSI', 'CRITICAL_DOWNGRADE_APPROVED',
                ['classification' => 'CRITICAL'], ['classification' => $classification, 'revision' => $record->revision, 'manager_user_id' => $actor->id], $reason);

            // Existing holds remain until assessed and individually released.
            return $record;
        });
    }

    /**
     * Manager Produksi makes a decision on a PENDING_MANAGER validation.
     * Only MANAGER_PRODUKSI may make manager decisions.
     */
    public function managerDecision(
        Prod05Validation $validation,
        User $actor,
        string $decision,
        array $data = []
    ): Prod05Validation {
        $this->roles->require($actor, 'MANAGER_PRODUKSI');

        return DB::transaction(function () use ($validation, $actor, $decision, $data): Prod05Validation {
            $locked = Prod05Validation::query()
                ->with(['briefVersion.marketingBrief'])
                ->whereKey($validation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'PENDING_MANAGER') {
                throw ValidationException::withMessages([
                    'validation' => 'Validasi tidak dalam status PENDING_MANAGER.',
                ]);
            }

            if (! in_array($decision, self::MANAGER_DECISIONS, true)) {
                throw ValidationException::withMessages([
                    'decision' => 'Keputusan Manager tidak valid.',
                ]);
            }

            $before = ['status' => $locked->status, 'manager_decision' => $locked->manager_decision];
            $after = ['status' => $decision, 'manager_decision' => $decision];

            $locked->status = $decision;
            $locked->manager_decision = $decision;
            $locked->manager_decision_at = now();
            $locked->provisional_data = array_merge($locked->provisional_data ?? [], $data['provisional_data'] ?? []);

            if ($decision === 'APPROVED_FOR_REVIEW') {
                $after['approved_for_review'] = true;
            } elseif ($decision === 'APPROVED_CONDITIONAL') {
                $after['approved_conditional'] = true;
                $after['conditions'] = $data['conditions'] ?? [];
            }

            $locked->save();

            $this->recordEvent($locked, $actor, 'MANAGER_PRODUKSI', 'MANAGER_DECISION_MADE', $before, $after, $data['reason'] ?? null);

            $this->notifyValidationResult($locked, 'MANAGER_DECISION_'.str_replace('_', '', $decision), 'Manager Produksi telah membuat keputusan: '.$decision);

            return $locked;
        });
    }

    /**
     * Release ON HOLD on a validation.
     * Only MANAGER_PRODUKSI may release holds.
     */
    public function releaseHold(Prod05Validation $validation, User $actor, string $reason): Prod05Validation
    {
        $this->roles->require($actor, 'MANAGER_PRODUKSI');
        throw ValidationException::withMessages(['activity' => 'Pelepasan massal dilarang. Lepaskan aktivitas terdampak satu per satu setelah evaluasi.']);
    }

    /**
     * Create an activity control record for ON HOLD scope tracking.
     */
    public function createActivityControl(
        Prod05Validation $validation,
        string $activityKey,
        string $activityType,
        array $downstreamDependencies = []
    ): Prod05ActivityControl {
        return Prod05ActivityControl::query()->create([
            'validation_id' => $validation->id,
            'activity_key' => $activityKey,
            'activity_type' => $activityType,
            'control_status' => 'ACTIVE',
            'downstream_dependencies' => $downstreamDependencies,
            'hold_requested_at' => null,
            'hold_released_at' => null,
        ]);
    }

    /**
     * Place an activity on HOLD. Critical conditions block Level 3 execution.
     */
    public function holdActivity(Prod05ActivityControl $activity, User $actor, string $reason): Prod05ActivityControl
    {
        $this->roles->require($actor, 'ASST_MANAGER_PRODUKSI');

        return DB::transaction(function () use ($activity, $actor, $reason) {
            $locked = Prod05ActivityControl::query()->whereKey($activity->id)->lockForUpdate()->firstOrFail();
            if ($locked->control_status === 'ON_HOLD') {
                throw ValidationException::withMessages(['activity' => 'Aktivitas sudah ON HOLD.']);
            }
            $before = $locked->control_status;
            $locked->control_status = 'ON_HOLD';
            $locked->hold_reason = $reason;
            $locked->hold_requested_at = now();
            $locked->save();
            $this->recordEvent($locked->validation, $actor, 'ASST_MANAGER_PRODUKSI', 'ACTIVITY_ON_HOLD',
                ['activity_key' => $locked->activity_key, 'control_status' => $before],
                ['activity_key' => $locked->activity_key, 'control_status' => 'ON_HOLD'], $reason);

            return $locked;
        });
    }

    /**
     * Release an activity from HOLD. Only Manager Produksi may release.
     */
    public function releaseActivity(Prod05ActivityControl $activity, User $actor, string $reason, bool $impactCleared = false): Prod05ActivityControl
    {
        $this->roles->require($actor, 'MANAGER_PRODUKSI');
        if (! $impactCleared || trim($reason) === '') {
            throw ValidationException::withMessages(['activity' => 'Evaluasi dampak dan alasan pelepasan wajib.']);
        }
        // F15-E/02B: Fail closed until independently verified
        // impact-resolution evidence is available.
        throw ValidationException::withMessages([
            'activity' => 'RELEASE HOLD ditolak: bukti penyelesaian dampak '
                .'belum diverifikasi oleh sistem.',
        ]);

        return DB::transaction(function () use ($activity, $actor, $reason) {
            // Lock validation first to serialize classification and release decisions.
            $validation = Prod05Validation::query()->whereKey($activity->validation_id)->lockForUpdate()->firstOrFail();
            $controls = $validation->activityControls()->orderBy('id')->lockForUpdate()->get();
            $locked = $controls->firstWhere('id', $activity->id);
            if (! $locked || $locked->control_status !== 'ON_HOLD') {
                throw ValidationException::withMessages(['activity' => 'Hanya aktivitas ON HOLD yang boleh dilepas.']);
            }
            // F15-E/02A: Verify transitive upstream dependencies.
            $byKey = $controls->keyBy('activity_key');
            $pending = [$locked->activity_key];
            $visited = [];

            while ($pending !== []) {
                $targetKey = array_pop($pending);

                if (isset($visited[$targetKey])) {
                    continue;
                }

                $visited[$targetKey] = true;

                foreach ($controls as $source) {
                    $children = $source->downstream_dependencies;

                    if (! is_array($children)
                        || ! in_array($targetKey, $children, true)) {
                        continue;
                    }

                    if ($source->id === $locked->id) {
                        continue;
                    }

                    if ($source->control_status === 'ON_HOLD') {
                        throw ValidationException::withMessages([
                            'activity' => 'Dependensi upstream masih ON HOLD: '
                                .$source->activity_key,
                        ]);
                    }

                    $pending[] = $source->activity_key;
                }
            }
            $locked->control_status = 'RELEASED';
            $locked->hold_released_at = now();
            $locked->save();
            $this->recordEvent($validation, $actor, 'MANAGER_PRODUKSI', 'ACTIVITY_HOLD_RELEASED',
                ['activity_key' => $locked->activity_key, 'control_status' => 'ON_HOLD'],
                ['activity_key' => $locked->activity_key, 'control_status' => 'RELEASED', 'impact_cleared' => true], $reason);

            return $locked;
        });
    }

    /**
     * Check if a Level 3 activity is blocked by unresolved Critical conditions.
     */
    public function isLevel3Blocked(Prod05ActivityControl $activity): bool
    {
        if (! in_array($activity->activity_type, self::LEVEL_3_ACTIVITY_TYPES, true)) {
            return false;
        }

        // Fail closed until downstream final technical data and subsequent approval
        // are integrated. PROD-05 must never issue a Level 3 clearance alone.
        return true;
    }

    /**
     * Revalidate after a revision change.
     * Minor = administrative check, Major = technical revalidation, Critical = risk evaluation + ON HOLD.
     */
    public function revalidateAfterRevision(
        Prod04BriefVersion $version,
        User $actor,
        string $impactClassification,
        int $revisionFrom,
        int $revisionTo
    ): Prod05Validation {
        $this->roles->require($actor, 'ASST_MANAGER_PRODUKSI');

        return DB::transaction(function () use ($version, $actor, $impactClassification, $revisionFrom, $revisionTo): Prod05Validation {
            $validation = Prod05Validation::query()->where('brief_version_id', $version->id)->firstOrFail();

            $before = ['status' => $validation->status];
            $validation->revision_from = $revisionFrom;
            $validation->revision_to = $revisionTo;
            $validation->impact_classification = $impactClassification;
            $validation->status = 'UNDER_REVIEW';
            $validation->save();

            Prod05RevisionClassification::query()->create([
                'validation_id' => $validation->id,
                'revision' => $revisionTo,
                'recommended_by' => $actor->id,
                'recommended_classification' => $impactClassification,
                'confirmed_by' => $actor->id,
                'confirmed_classification' => $impactClassification,
                'manager_approval_for_downgrade' => false,
                'confirmation_reason' => 'Revalidasi setelah revisi versi '.$revisionFrom.' ke '.$revisionTo,
                'occurred_at' => now(),
            ]);

            $this->recordEvent($validation, $actor, 'ASST_MANAGER_PRODUKSI', 'REVALIDATION_REQUESTED', $before, [
                'status' => 'UNDER_REVIEW',
                'impact_classification' => $impactClassification,
                'revision_from' => $revisionFrom,
                'revision_to' => $revisionTo,
            ]);

            if ($impactClassification === 'CRITICAL') {
                $this->applyCriticalHold($validation, $actor);
            }

            return $validation;
        });
    }

    private function applyCriticalHold(Prod05Validation $validation, User $actor, ?array $affectedActivityKeys = null): void
    {
        // Null = unverified scope => fail closed by holding all activities.
        // An explicit list must contain valid keys; empty lists never prove no impact.
        $controls = $validation->activityControls()->orderBy('id')->get();
        $known = $controls->pluck('activity_key')->all();
        $verified = $affectedActivityKeys !== null && $affectedActivityKeys !== []
            && count(array_diff($affectedActivityKeys, $known)) === 0;
        $scope = $verified ? array_values(array_unique($affectedActivityKeys)) : $known;
        // Reachability closure: hold impacted activities and every downstream dependent.
        $queue = $scope;
        $visited = array_fill_keys($scope, true);
        while ($queue !== []) {
            $key = array_shift($queue);
            $activity = $controls->firstWhere('activity_key', $key);
            foreach (($activity?->downstream_dependencies ?? []) as $dependentKey) {
                if (! in_array($dependentKey, $known, true)) {
                    // Unknown mapping cannot prove independence: revert to all-on-hold.
                    $visited = array_fill_keys($known, true);
                    $queue = [];
                    break;
                }
                if (! isset($visited[$dependentKey])) {
                    $visited[$dependentKey] = true;
                    $queue[] = $dependentKey;
                }
            }
        }
        $heldKeys = [];
        foreach ($controls as $activity) {
            if (! isset($visited[$activity->activity_key]) || $activity->control_status === 'ON_HOLD') {
                continue;
            }
            $activity->control_status = 'ON_HOLD';
            $activity->hold_reason = 'CRITICAL: area terdampak atau dependensi';
            $activity->hold_requested_at = now();
            $activity->hold_released_at = null;
            $activity->save();
            $heldKeys[] = $activity->activity_key;
        }
        $this->recordEvent($validation, $actor, 'ASST_MANAGER_PRODUKSI', 'CRITICAL_ACTIVITY_HOLD',
            ['document_status' => $validation->status],
            ['document_status' => $validation->status, 'held_activity_keys' => $heldKeys,
                'requested_scope' => $affectedActivityKeys, 'unverified_scope_fallback' => ! $verified],
            'HOLD CRITICAL sesuai area terdampak dan dependensinya.');
    }

    private function recordEvent(
        Prod05Validation $validation,
        ?User $actor,
        string $actorRole,
        string $eventType,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null
    ): void {
        Prod05ValidationEvent::query()->create([
            'validation_id' => $validation->id,
            'actor_user_id' => $actor?->id,
            'actor_role' => $actorRole,
            'event_type' => $eventType,
            'before_status' => $before['status'] ?? null,
            'after_status' => $after['status'] ?? null,
            'before_data' => $before,
            'after_data' => $after,
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
    }

    private function notifyValidationResult(Prod05Validation $validation, string $event, string $message): void
    {
        try {
            $version = $validation->briefVersion;
            if ($version === null) {
                return;
            }

            $marketing = $version->marketingBrief->marketing;
            $marketing->notify(new ProductionWorkflowNotification($event, $message, [
                'validation_id' => $validation->id,
                'brief_version_id' => $version->id,
                'document_number' => $version->document_number,
                'revision' => $version->revision,
            ]));
        } catch (QueryException $exception) {
            Log::error('PROD-05 notification failed.', [
                'validation_id' => $validation->id,
                'exception' => $exception,
            ]);
        }
    }
}
