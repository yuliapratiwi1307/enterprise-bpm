<?php

namespace App\Console\Commands;

use App\Services\BrowserTestingRuntimeGuard;
use App\Services\Prod04BrowserAccountProvisioner;
use App\Services\Prod04E2ECredentialStore;
use Illuminate\Console\Command;
use RuntimeException;

class ProvisionProd04BrowserAccounts extends Command
{
    protected $signature = 'prod04:provision-browser-accounts';

    protected $description = 'Prepare the three PROD-04 E2E roles and accounts on the isolated browser database.';

    /**
     * @var array<string, array{name: string, email: string, role_name: string}>
     */
    private const ACCOUNTS = [
        'ASST_MANAGER_PRODUKSI' => [
            'name' => 'E2E Asst Manager Produksi',
            'email' => 'prod04-asst-manager-e2e@example.test',
            'role_name' => 'Asst Manager Produksi',
        ],
        'ADMIN_PRODUKSI' => [
            'name' => 'E2E Admin Produksi',
            'email' => 'prod04-production-admin-e2e@example.test',
            'role_name' => 'Admin Produksi',
        ],
        'ADMIN_BPM' => [
            'name' => 'E2E Administrator BPM',
            'email' => 'prod04-bpm-admin-e2e@example.test',
            'role_name' => 'Administrator BPM',
        ],
    ];

    public function handle(
        BrowserTestingRuntimeGuard $guard,
        Prod04E2ECredentialStore $credentialStore,
        Prod04BrowserAccountProvisioner $provisioner,
    ): int {
        $guard->verify();
        $this->assertRestrictedBrowserDatabaseRole($guard);

        $home = getenv('HOME');
        if (! is_string($home) || $home === '' || ! is_dir($home)) {
            throw new RuntimeException('A private home directory is required for E2E credentials.');
        }

        $repository = realpath(base_path());
        $credentialPath = rtrim($home, DIRECTORY_SEPARATOR).'/.enterprise_bpm_browser_prod04_e2e_credentials.json';
        if ($repository !== false && str_starts_with($credentialPath, $repository.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('The E2E credential file must be outside the repository.');
        }

        $lockPath = $credentialPath.'.lock';
        if (is_link($lockPath)) {
            throw new RuntimeException('The credential lock file may not be a symbolic link.');
        }

        $lock = fopen($lockPath, 'c');
        if ($lock === false) {
            throw new RuntimeException('Unable to open the private credential lock file.');
        }
        if (! chmod($lockPath, 0600)) {
            fclose($lock);
            throw new RuntimeException('Unable to restrict permissions on the private credential lock file.');
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to acquire the E2E credential lock.');
            }

            $credentialFileExists = file_exists($credentialPath);
            $credentials = $credentialFileExists
                ? $credentialStore->read($credentialPath, self::ACCOUNTS)
                : $credentialStore->generate(self::ACCOUNTS);

            $createdCredentialFile = false;
            if (! $credentialFileExists) {
                $credentialStore->write($credentialPath, $credentials);
                $createdCredentialFile = true;
            }

            try {
                $provisioner->provision(self::ACCOUNTS, $credentials);
            } catch (\Throwable $exception) {
                if ($createdCredentialFile && is_file($credentialPath) && ! is_link($credentialPath)) {
                    unlink($credentialPath);
                }

                throw $exception;
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $this->components->info('PROD-04 E2E role accounts are prepared on enterprise_bpm_browser.');
        $this->line('Private credentials: '.$credentialPath);

        return self::SUCCESS;
    }

    private function assertRestrictedBrowserDatabaseRole(BrowserTestingRuntimeGuard $guard): void
    {
        $guard->verifyBrowserDatabaseIdentityAndRole();
    }
}
