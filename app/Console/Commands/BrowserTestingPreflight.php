<?php

namespace App\Console\Commands;

use App\Services\BrowserTestingRuntimeGuard;
use Illuminate\Console\Command;
use Throwable;

class BrowserTestingPreflight extends Command
{
    protected $signature = 'browser-testing:preflight';

    protected $description = 'Verify isolated browser testing runtime before starting the test server.';

    public function handle(BrowserTestingRuntimeGuard $guard): int
    {
        try {
            $guard->verify();
        } catch (Throwable $exception) {
            $this->components->error('Browser testing preflight failed; no application request should be served.');

            return self::FAILURE;
        }

        $this->components->info('Browser testing preflight passed for enterprise_bpm_browser.');

        return self::SUCCESS;
    }
}
