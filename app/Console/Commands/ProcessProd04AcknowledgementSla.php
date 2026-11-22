<?php

namespace App\Console\Commands;

use App\Services\Prod04Workflow;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ProcessProd04AcknowledgementSla extends Command
{
    protected $signature = 'prod04:process-acknowledgement-sla';

    protected $description = 'Process PROD-04 work-hour reminders and acknowledgement overdue indicators.';

    public function handle(Prod04Workflow $workflow): int
    {
        try {
            $workflow->processSla();
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('PROD-04 acknowledgement SLA processed.');

        return self::SUCCESS;
    }
}
