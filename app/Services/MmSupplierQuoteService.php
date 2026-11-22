<?php

namespace App\Services;

use App\Models\MmMaterialVariant;
use App\Models\MmSupplierQuote;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MmSupplierQuoteService
{
    public function recordQuote(array $data, ?int $actorId = null): MmSupplierQuote
    {
        MmSafety::check();
        MmSafety::actor($actorId);
        $v = MmSafety::validate($data, $this->quoteRules());
        $v['currency'] = strtoupper($v['currency'] ?? 'IDR');
        $v['recorded_by'] = $actorId;

        return DB::transaction(function () use ($v, $actorId): MmSupplierQuote {
            $variant = MmMaterialVariant::query()
                ->whereKey($v['material_variant_id'])
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::active('mm_material_variants', (int) $variant->id, 'material_variant_id');
            MmSafety::active('mm_materials', (int) $variant->material_id, 'material_id');
            MmSafety::active('mm_suppliers', (int) $v['supplier_id'], 'supplier_id');
            MmSafety::active('mm_units', (int) $v['price_unit_id'], 'price_unit_id');

            $quote = new MmSupplierQuote($v);
            $quote->is_active = true;
            $quote->save();
            $this->recordQuoteEvent(
                $quote,
                MmSupplierQuote::STATUS_PENDING,
                null,
                MmSupplierQuote::STATUS_PENDING,
                $actorId,
                now(),
            );

            return $quote;
        });
    }

    public function updateQuote(MmSupplierQuote $quote, array $data): MmSupplierQuote
    {
        MmSafety::check($quote, 'mm_supplier_quotes');
        $v = MmSafety::validate($data, $this->quoteRules(true));
        $v['currency'] = strtoupper($v['currency'] ?? 'IDR');

        return DB::transaction(function () use ($quote, $v): MmSupplierQuote {
            $lockedQuote = MmSupplierQuote::query()
                ->whereKey($quote->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($lockedQuote, 'mm_supplier_quotes');
            if ($lockedQuote->approval_status !== MmSupplierQuote::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'quote' => 'Penawaran yang sudah diputuskan tidak dapat diubah. Catat penawaran baru.',
                ]);
            }
            if ((int) $v['price_unit_id'] !== (int) $lockedQuote->price_unit_id) {
                MmSafety::active('mm_units', (int) $v['price_unit_id'], 'price_unit_id');
            }
            $lockedQuote->update($v);

            return $lockedQuote->refresh();
        });
    }

    public function approveQuote(
        MmSupplierQuote $quote,
        User $actor,
        ?string $reason = null,
    ): MmSupplierQuote {
        return $this->transitionApproval($quote, $actor, MmSupplierQuote::STATUS_APPROVED, $reason);
    }

    public function rejectQuote(
        MmSupplierQuote $quote,
        User $actor,
        string $reason,
    ): MmSupplierQuote {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return $this->transitionApproval($quote, $actor, MmSupplierQuote::STATUS_REJECTED, $reason);
    }

    public function setQuoteActive(
        MmSupplierQuote $quote,
        bool $isActive,
        User $actor,
        ?string $reason = null,
    ): MmSupplierQuote {
        abort_unless(MmAuthorization::canManage($actor), 403,
            'Hanya Procurement yang dapat mengubah status aktif penawaran.');
        MmSafety::check($quote, 'mm_supplier_quotes');
        MmSafety::actor((int) $actor->getKey());
        $reason = trim((string) $reason);
        $reason = $reason === '' ? null : $reason;

        return DB::transaction(function () use ($quote, $isActive, $actor, $reason): MmSupplierQuote {
            $lockedQuote = MmSupplierQuote::query()
                ->whereKey($quote->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($lockedQuote, 'mm_supplier_quotes');
            $previousIsActive = (bool) $lockedQuote->is_active;

            if ($previousIsActive === $isActive) {
                return $lockedQuote->refresh();
            }

            $lockedQuote->is_active = $isActive;
            $lockedQuote->save();
            $actedAt = now();
            $eventType = $isActive ? 'ACTIVATED' : 'DEACTIVATED';
            $this->recordQuoteEvent(
                $lockedQuote,
                $eventType,
                $lockedQuote->approval_status,
                $lockedQuote->approval_status,
                (int) $actor->getKey(),
                $actedAt,
                $reason,
                $previousIsActive,
                $isActive,
            );

            return $lockedQuote->refresh();
        });
    }

    private function transitionApproval(
        MmSupplierQuote $quote,
        User $actor,
        string $targetStatus,
        ?string $reason,
    ): MmSupplierQuote {
        abort_unless(MmAuthorization::canApproveQuotes($actor), 403,
            'Hanya ADMIN_BPM dan Procurement yang dapat menyetujui atau menolak penawaran.');
        MmSafety::check($quote, 'mm_supplier_quotes');
        MmSafety::actor((int) $actor->getKey());

        return DB::transaction(function () use ($quote, $actor, $targetStatus, $reason): MmSupplierQuote {
            $lockedQuote = MmSupplierQuote::query()
                ->whereKey($quote->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            MmSafety::check($lockedQuote, 'mm_supplier_quotes');

            if (! $lockedQuote->is_active) {
                throw ValidationException::withMessages([
                    'quote' => 'Penawaran nonaktif tidak dapat disetujui atau ditolak.',
                ]);
            }

            if ($lockedQuote->approval_status !== MmSupplierQuote::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'quote' => 'Penawaran ini sudah diputuskan oleh pengguna lain.',
                ]);
            }

            if (
                $targetStatus === MmSupplierQuote::STATUS_APPROVED
                && (
                    $lockedQuote->recorded_by === null
                    || (int) $lockedQuote->recorded_by === (int) $actor->getKey()
                )
            ) {
                throw ValidationException::withMessages([
                    'quote' => 'Pembuat penawaran tidak dapat menyetujui penawarannya sendiri.',
                ]);
            }

            $actedAt = now();
            $fromStatus = $lockedQuote->approval_status;
            $lockedQuote->approval_status = $targetStatus;

            if ($targetStatus === MmSupplierQuote::STATUS_APPROVED) {
                $lockedQuote->approved_by = $actor->getKey();
                $lockedQuote->approved_at = $actedAt;
            } else {
                $lockedQuote->rejected_by = $actor->getKey();
                $lockedQuote->rejected_at = $actedAt;
                $lockedQuote->rejection_reason = $reason;
            }

            $lockedQuote->save();
            $this->recordQuoteEvent(
                $lockedQuote,
                $targetStatus,
                $fromStatus,
                $targetStatus,
                (int) $actor->getKey(),
                $actedAt,
                $reason,
            );

            return $lockedQuote->refresh();
        });
    }

    private function recordQuoteEvent(
        MmSupplierQuote $quote,
        string $eventType,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorId,
        Carbon $actedAt,
        ?string $reason = null,
        ?bool $previousIsActive = null,
        ?bool $newIsActive = null,
    ): void {
        DB::table('mm_supplier_quote_approval_events')->insert([
            'quote_id' => $quote->getKey(),
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_id' => $actorId,
            'acted_at' => $actedAt,
            'reason' => $reason,
            'previous_is_active' => $previousIsActive,
            'new_is_active' => $newIsActive,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function quoteRules(bool $updating = false): array
    {
        $rules = [
            'price_unit_id' => ['required', 'integer', Rule::exists('mm_units', 'id')],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,4', 'max:9999999999999999'],
            'currency' => ['required', 'string', 'size:3', 'alpha:ascii'],
            'quoted_on' => ['required', 'date_format:Y-m-d'],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:quoted_on'],
            'supplier_reference' => ['nullable', 'string', 'max:120'],
            'source_document_path' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];

        if (! $updating) {
            $rules = [
                'material_variant_id' => ['required', 'integer', Rule::exists('mm_material_variants', 'id')],
                'supplier_id' => ['required', 'integer', Rule::exists('mm_suppliers', 'id')],
                ...$rules,
            ];
        }

        return $rules;
    }
}
