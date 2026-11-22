<?php

namespace App\Services;

use App\Models\DesignRequest;
use App\Models\MarketingBrief;
use App\Models\ProductionWorkflowEvent;
use App\Models\User;

class ProductionWorkflowAudit
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        MarketingBrief $brief,
        ?DesignRequest $designRequest,
        User $actor,
        string $role,
        string $event,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?int $attachmentVersion = null,
    ): ProductionWorkflowEvent {
        return ProductionWorkflowEvent::query()->create([
            'marketing_brief_id' => $brief->id,
            'design_request_id' => $designRequest?->id,
            'actor_user_id' => $actor->id,
            'actor_role' => $role,
            'event_type' => $event,
            'before_data' => $before,
            'after_data' => $after,
            'reason' => $reason,
            'attachment_version' => $attachmentVersion,
            'occurred_at' => now(),
        ]);
    }
}
