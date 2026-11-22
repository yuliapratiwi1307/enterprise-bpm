<?php

namespace App\Services;

use App\Models\Prod04BriefVersion;
use App\Models\Prod04DistributionRecipient;
use App\Models\Prod04DocumentAccess;
use App\Models\Prod04ValidationTask;
use Illuminate\Support\Carbon;

class Prod04RecipientAccess
{
    public function grant(
        Prod04BriefVersion $version,
        Prod04DistributionRecipient $recipient,
        Carbon $availableAt,
    ): void {
        Prod04DocumentAccess::query()->firstOrCreate(
            ['brief_version_id' => $version->id, 'user_id' => $recipient->user_id],
            ['obligation_type' => $recipient->obligation_type, 'available_at' => $availableAt],
        );

        if ($recipient->obligation_type === 'PRIMARY') {
            Prod04ValidationTask::query()->firstOrCreate(
                ['brief_version_id' => $version->id],
                ['assigned_to' => $recipient->user_id, 'status' => 'AVAILABLE', 'available_at' => $availableAt],
            );
        }
    }
}
