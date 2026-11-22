<?php

namespace App\Services;

use App\Models\MarketingBrief;

class MarketingBriefReadiness
{
    public const COMPONENTS = [
        'work_type' => 'Jenis pekerjaan',
        'work_concept' => 'Konsep pekerjaan',
        'visualization' => 'Visualisasi',
        'work_specification' => 'Spesifikasi pekerjaan',
        'duration' => 'Durasi',
        'quantity' => 'Kuantitas',
    ];

    private const OPEN_DESIGN_STATUSES = [
        'AVAILABLE',
        'IN_PROGRESS',
        'SUBMITTED_FOR_REVIEW',
        'REVISION_REQUIRED',
        'CANCELLATION_PENDING_ACK',
    ];

    /**
     * @return array{ready: bool, status: string, missing_components: array<int, string>, critical_outstanding: array<int, int>, notes: array<int, array<string, mixed>>}
     */
    public function evaluate(MarketingBrief $brief): array
    {
        $components = $brief->components()->get()->keyBy('component');
        $missing = [];

        foreach (self::COMPONENTS as $key => $label) {
            $component = $components->get($key);
            if (
                $component === null
                || blank($component->value)
                || ($component->is_provisional && blank($component->information_basis))
            ) {
                $missing[] = $label;
            }
        }

        $criticalOutstanding = [];
        $notes = [];

        foreach ($brief->designRequests()->get() as $request) {
            if ($request->classification === 'CRITICAL' && $request->status !== 'COMPLETED') {
                $criticalOutstanding[] = $request->id;
            }

            if (
                $request->classification === 'NONCRITICAL'
                && in_array($request->status, self::OPEN_DESIGN_STATUSES, true)
            ) {
                if ($request->current_pic_id === null || $request->deadline_at === null || blank($request->impact)) {
                    $missing[] = "PIC, deadline, dan dampak Design Request #{$request->id}";
                } else {
                    $notes[] = [
                        'design_request_id' => $request->id,
                        'pic_user_id' => $request->current_pic_id,
                        'deadline_at' => $request->deadline_at->toISOString(),
                        'impact' => $request->impact,
                        'status' => $request->status,
                    ];
                }
            }
        }

        $ready = $missing === [] && $criticalOutstanding === [];

        return [
            'ready' => $ready,
            'status' => ! $ready
                ? 'DRAFT'
                : ($notes === [] ? 'READY_FOR_SUBMISSION' : 'READY_WITH_NOTES'),
            'missing_components' => array_values(array_unique($missing)),
            'critical_outstanding' => $criticalOutstanding,
            'notes' => $notes,
        ];
    }
}
