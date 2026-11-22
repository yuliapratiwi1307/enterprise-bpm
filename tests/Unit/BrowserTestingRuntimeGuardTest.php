<?php

namespace Tests\Unit;

use App\Services\BrowserTestingRuntimeGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BrowserTestingRuntimeGuardTest extends TestCase
{
    public function test_explicitly_isolated_browser_testing_configuration_is_accepted(): void
    {
        $this->assertNull(BrowserTestingRuntimeGuard::assertConfiguration($this->validConfiguration()));
    }

    #[DataProvider('unsafeConfigurationProvider')]
    public function test_unsafe_runtime_configuration_is_rejected(string $path, mixed $value): void
    {
        $configuration = $this->validConfiguration();
        data_set($configuration, $path, $value);

        $this->expectException(RuntimeException::class);
        BrowserTestingRuntimeGuard::assertConfiguration($configuration);
    }

    public function test_verify_browser_database_identity_and_role_is_public_for_reuse(): void
    {
        $reflection = new \ReflectionMethod(BrowserTestingRuntimeGuard::class, 'verifyBrowserDatabaseIdentityAndRole');
        $this->assertTrue($reflection->isPublic(), 'verifyBrowserDatabaseIdentityAndRole must be public for reuse by provisioning command.');
    }

    public function test_browser_database_identity_and_least_privilege_role_are_accepted(): void
    {
        $this->assertNull(
            BrowserTestingRuntimeGuard::assertBrowserDatabaseIdentityAndRole($this->validIdentity())
        );
    }

    #[DataProvider('unsafeIdentityProvider')]
    public function test_unsafe_browser_database_identity_or_role_is_rejected(string $key, mixed $value): void
    {
        $identity = $this->validIdentity();
        $identity[$key] = $value;

        $this->expectException(RuntimeException::class);
        BrowserTestingRuntimeGuard::assertBrowserDatabaseIdentityAndRole($identity);
    }

    public static function unsafeConfigurationProvider(): array
    {
        return [
            'guard not explicitly enabled' => ['browser_guard_enabled', false],
            'wrong application environment' => ['app_environment', 'production'],
            'wrong default database connection' => ['database_default', 'mysql'],
            'active database configured' => ['database_connections.pgsql.database', 'enterprise_bpm'],
            'PHPUnit database configured for browser runtime' => [
                'database_connections.pgsql.database',
                'enterprise_bpm_test',
            ],
            'wrong database role' => ['database_connections.pgsql.username', 'enterprise_bpm_user'],
            'PHPUnit role configured for browser runtime' => [
                'database_connections.pgsql.username',
                'enterprise_bpm_test_user',
            ],
            'wrong database host' => ['database_connections.pgsql.host', 'localhost'],
            'database URL configured' => ['database_connections.pgsql.url', 'postgres://unexpected'],
            'active URL configured' => ['db_url', 'postgres://unexpected'],
            'wrong cache driver' => ['cache_default', 'database'],
            'session storage not isolated' => ['session_driver', 'array'],
            'session storage path not isolated' => [
                'session_files',
                '/workspace/storage/framework/sessions',
            ],
            'uploaded files storage is not isolated from application storage' => [
                'local_storage_root',
                '/workspace/storage/shared-files',
            ],
            'uploaded files use a non-local disk' => ['local_storage_driver', 's3'],
            'session connection points away from testing' => ['session_connection', 'mysql'],
            'queue is asynchronous' => ['queue_default', 'database'],
            'queue database connection points away from testing' => [
                'queue_connections.database.connection',
                'mysql',
            ],
            'cache database connection points away from testing' => [
                'cache_stores.database.connection',
                'mysql',
            ],
            'batch queue database points away from testing' => ['queue_batch_database', 'mysql'],
            'failed queue database points away from testing' => ['queue_failed_database', 'mysql'],
            'alternate connection points to active database' => [
                'database_connections.mysql.database',
                'enterprise_bpm',
            ],
            'alternate connection points to test database' => [
                'database_connections.mysql.database',
                'enterprise_bpm_test',
            ],
            'alternate connection URL bypasses target checks' => [
                'database_connections.mysql.url',
                'postgres://unexpected',
            ],
            'unexpected alternate connection is rejected' => [
                'database_connections.oracle.driver',
                'oracle',
            ],
        ];
    }

    public static function unsafeIdentityProvider(): array
    {
        return [
            'wrong database' => ['database_name', 'enterprise_bpm'],
            'wrong role' => ['current_role', 'enterprise_bpm_user'],
            'wrong session role' => ['session_role', 'enterprise_bpm_test_user'],
            'wrong driver' => ['driver', 'mysql'],
            'superuser role' => ['is_superuser', 1],
            'role can create databases' => ['can_create_database', 1],
            'role can create roles' => ['can_create_role', 1],
            'role can replicate' => ['can_replicate', 1],
            'role bypasses row security' => ['can_bypass_rls', 1],
            'role inherits memberships' => ['inherits_memberships', 1],
            'role cannot login' => ['can_login', 0],
            'role does not own browser database' => ['owns_browser_database', 0],
            'role cannot create in browser database' => ['can_create_in_browser_database', 0],
            'role has memberships' => ['has_role_memberships', 1],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validConfiguration(): array
    {
        return [
            'browser_guard_enabled' => true,
            'app_environment' => 'testing',
            'database_default' => 'pgsql',
            'database_connections' => [
                'pgsql' => [
                    'driver' => 'pgsql',
                    'host' => '127.0.0.1',
                    'port' => '5432',
                    'database' => 'enterprise_bpm_browser',
                    'username' => 'enterprise_bpm_browser_user',
                    'url' => '',
                ],
            ],
            'db_url' => '',
            'cache_default' => 'array',
            'cache_stores' => [
                'array' => ['driver' => 'array'],
                'database' => ['driver' => 'database', 'connection' => 'pgsql'],
            ],
            'session_driver' => 'file',
            'session_connection' => 'pgsql',
            'session_files' => '/workspace/storage/framework/browser-testing/sessions',
            'expected_session_files' => '/workspace/storage/framework/browser-testing/sessions',
            'local_storage_driver' => 'local',
            'local_storage_root' => '/workspace/storage/app/private',
            'expected_application_storage_root' => '/workspace/storage/app/private',
            'expected_local_storage_root' => '/workspace/storage/framework/browser-testing/files',
            'queue_default' => 'sync',
            'queue_connections' => [
                'sync' => ['driver' => 'sync'],
                'database' => ['driver' => 'database', 'connection' => 'pgsql'],
            ],
            'queue_batch_database' => 'pgsql',
            'queue_failed_database' => 'pgsql',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validIdentity(): array
    {
        return [
            'driver' => 'pgsql',
            'database_name' => 'enterprise_bpm_browser',
            'current_role' => 'enterprise_bpm_browser_user',
            'session_role' => 'enterprise_bpm_browser_user',
            'is_superuser' => 0,
            'can_create_database' => 0,
            'can_create_role' => 0,
            'can_replicate' => 0,
            'can_bypass_rls' => 0,
            'inherits_memberships' => 0,
            'can_login' => 1,
            'owns_browser_database' => 1,
            'can_create_in_browser_database' => 1,
            'has_role_memberships' => 0,
        ];
    }
}
