<?php

namespace Tests\Unit;

use App\Services\Prod04E2ECredentialStore;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class Prod04E2ECredentialStoreTest extends TestCase
{
    public function test_generated_credentials_are_written_and_read_from_a_private_file(): void
    {
        $directory = $this->createPrivateDirectory();
        $path = $directory.DIRECTORY_SEPARATOR.'credentials.json';
        $store = new Prod04E2ECredentialStore;
        $accounts = $this->accounts();
        $credentials = $store->generate($accounts);

        try {
            $store->write($path, $credentials);

            $this->assertSame($credentials, $store->read($path, $accounts));
            $this->assertSame(0600, fileperms($path) & 0777);
            $this->assertSame(64, strlen($credentials['ADMIN_BPM']['password']));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            rmdir($directory);
        }
    }

    public function test_public_credential_file_is_rejected(): void
    {
        $directory = $this->createPrivateDirectory();
        $path = $directory.DIRECTORY_SEPARATOR.'credentials.json';
        $store = new Prod04E2ECredentialStore;

        try {
            $store->write($path, $store->generate($this->accounts()));
            chmod($path, 0644);

            $this->expectException(RuntimeException::class);
            $store->read($path, $this->accounts());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            rmdir($directory);
        }
    }

    private function createPrivateDirectory(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'prod04-e2e-');
        if ($path === false || ! unlink($path) || ! mkdir($path, 0700)) {
            throw new RuntimeException('Unable to create a private test directory.');
        }

        return $path;
    }

    /**
     * @return array<string, array{name: string, email: string, role_name: string}>
     */
    private function accounts(): array
    {
        return [
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
    }
}
