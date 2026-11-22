<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;

class BrowserTestingRuntimeGuard
{
    private const DATABASE = 'enterprise_bpm_browser';

    private const ROLE = 'enterprise_bpm_browser_user';

    public function isExplicitlyEnabled(): bool
    {
        return env('BROWSER_TESTING_GUARD') === '1'
            || config('browser-testing.enabled') === true;
    }

    public function verify(): void
    {
        if (! $this->isExplicitlyEnabled() || app()->environment() !== 'testing') {
            throw new RuntimeException('Browser testing guard is not explicitly enabled for the testing environment.');
        }

        $this->assertConfiguration($this->configurationSnapshot());
        $this->restrictRuntimeConnections();
        $this->verifyBrowserDatabaseIdentityAndRole();
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    public static function assertConfiguration(array $configuration): void
    {
        $connections = $configuration['database_connections'] ?? [];
        $pgsql = $connections['pgsql'] ?? [];

        if (
            ($configuration['browser_guard_enabled'] ?? false) !== true
            || ($configuration['app_environment'] ?? null) !== 'testing'
            || ($configuration['database_default'] ?? null) !== 'pgsql'
            || count($connections) !== 1
            || ! array_key_exists('pgsql', $connections)
            || ($pgsql['driver'] ?? null) !== 'pgsql'
            || ($pgsql['host'] ?? null) !== '127.0.0.1'
            || (string) ($pgsql['port'] ?? '') !== '5432'
            || ($pgsql['database'] ?? null) !== self::DATABASE
            || ($pgsql['username'] ?? null) !== self::ROLE
            || ! empty($pgsql['url'] ?? null)
            || ! in_array($configuration['db_url'] ?? null, [null, ''], true)
            || ($configuration['cache_default'] ?? null) !== 'array'
            || (($configuration['cache_stores']['array']['driver'] ?? null) !== 'array')
            || ($configuration['session_driver'] ?? null) !== 'file'
            || ! in_array($configuration['session_connection'] ?? null, [null, '', 'pgsql'], true)
            || ($configuration['session_files'] ?? null) !== ($configuration['expected_session_files'] ?? null)
            || ! is_string($configuration['expected_session_files'] ?? null)
            || ! str_ends_with(
                $configuration['expected_session_files'],
                '/storage/framework/browser-testing/sessions'
            )
            || ($configuration['local_storage_driver'] ?? null) !== 'local'
            || ! in_array(
                $configuration['local_storage_root'] ?? null,
                [
                    $configuration['expected_application_storage_root'] ?? null,
                    $configuration['expected_local_storage_root'] ?? null,
                ],
                true
            )
            || ! is_string($configuration['expected_application_storage_root'] ?? null)
            || ! is_string($configuration['expected_local_storage_root'] ?? null)
            || ! str_ends_with(
                $configuration['expected_local_storage_root'],
                '/storage/framework/browser-testing/files'
            )
            || ($configuration['queue_default'] ?? null) !== 'sync'
            || (($configuration['queue_connections']['sync']['driver'] ?? null) !== 'sync')
            || ! in_array(
                $configuration['queue_connections']['database']['connection'] ?? null,
                [null, '', 'pgsql'],
                true
            )
            || ! in_array(
                $configuration['cache_stores']['database']['connection'] ?? null,
                [null, '', 'pgsql'],
                true
            )
            || ($configuration['queue_batch_database'] ?? null) !== 'pgsql'
            || ($configuration['queue_failed_database'] ?? null) !== 'pgsql'
        ) {
            throw new RuntimeException('Browser testing runtime configuration is not isolated to enterprise_bpm_browser.');
        }

    }

    /**
     * @return array<string, mixed>
     */
    private function configurationSnapshot(): array
    {
        return [
            'browser_guard_enabled' => $this->isExplicitlyEnabled(),
            'app_environment' => app()->environment(),
            'database_default' => config('database.default'),
            'database_connections' => config('database.connections', []),
            'db_url' => env('DB_URL'),
            'cache_default' => config('cache.default'),
            'cache_stores' => config('cache.stores', []),
            'session_driver' => config('session.driver'),
            'session_connection' => config('session.connection'),
            'session_files' => config('session.files'),
            'expected_session_files' => storage_path('framework/browser-testing/sessions'),
            'local_storage_driver' => config('filesystems.disks.local.driver'),
            'local_storage_root' => config('filesystems.disks.local.root'),
            'expected_application_storage_root' => storage_path('app/private'),
            'expected_local_storage_root' => storage_path('framework/browser-testing/files'),
            'queue_default' => config('queue.default'),
            'queue_connections' => config('queue.connections', []),
            'queue_batch_database' => config('queue.batching.database'),
            'queue_failed_database' => config('queue.failed.database'),
        ];
    }

    private function restrictRuntimeConnections(): void
    {
        foreach (array_keys(config('database.connections', [])) as $connectionName) {
            if ($connectionName !== 'pgsql') {
                DB::purge($connectionName);
            }
        }

        $pgsql = config('database.connections.pgsql');
        $sessionDirectory = storage_path('framework/browser-testing/sessions');
        $browserFilesDirectory = storage_path('framework/browser-testing/files');
        $this->preparePrivateDirectory($sessionDirectory, 'session');
        $this->preparePrivateDirectory($browserFilesDirectory, 'uploaded-file');
        $localDisk = config('filesystems.disks.local');
        $localDisk['root'] = $browserFilesDirectory;

        config([
            'database.default' => 'pgsql',
            'database.connections' => ['pgsql' => $pgsql],
            'cache.default' => 'array',
            'cache.stores' => ['array' => ['driver' => 'array', 'serialize' => false]],
            'session.driver' => 'file',
            'session.files' => $sessionDirectory,
            'filesystems.disks.local' => $localDisk,
            'queue.default' => 'sync',
            'queue.connections' => ['sync' => ['driver' => 'sync']],
            'queue.batching.database' => 'pgsql',
            'queue.failed.database' => 'pgsql',
        ]);
        Storage::forgetDisk('local');
    }

    private function preparePrivateDirectory(string $directory, string $purpose): void
    {
        if (is_link($directory)) {
            throw new RuntimeException("Browser testing {$purpose} directory must not be a symbolic link.");
        }

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create the private browser testing {$purpose} directory.");
        }

        if (! chmod($directory, 0700)) {
            throw new RuntimeException("Could not restrict browser testing {$purpose} directory permissions.");
        }

        clearstatcache(true, $directory);
        $permissions = fileperms($directory);
        $realPath = realpath($directory);

        if (
            $permissions === false
            || ($permissions & 0077) !== 0
            || $realPath !== $directory
        ) {
            throw new RuntimeException("Browser testing {$purpose} directory path or permissions are unsafe.");
        }
    }

    public function verifyBrowserDatabaseIdentityAndRole(): void
    {
        $connection = DB::connection('pgsql');
        $driver = $connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $identity = $connection->selectOne(<<<'SQL'
            select
                current_database() as database_name,
                current_user as current_role,
                session_user as session_role,
                case when browser_role.rolsuper then 1 else 0 end as is_superuser,
                case when browser_role.rolcreatedb then 1 else 0 end as can_create_database,
                case when browser_role.rolcreaterole then 1 else 0 end as can_create_role,
                case when browser_role.rolreplication then 1 else 0 end as can_replicate,
                case when browser_role.rolbypassrls then 1 else 0 end as can_bypass_rls,
                case when browser_role.rolinherit then 1 else 0 end as inherits_memberships,
                case when browser_role.rolcanlogin then 1 else 0 end as can_login,
                case when browser_database.datdba = browser_role.oid then 1 else 0 end as owns_browser_database,
                case when has_database_privilege(browser_role.oid, browser_database.oid, 'CREATE') then 1 else 0 end as can_create_in_browser_database,
                case when exists (
                    select 1 from pg_auth_members membership
                    where membership.member = browser_role.oid
                ) then 1 else 0 end as has_role_memberships
            from pg_roles browser_role
            join pg_database browser_database on browser_database.datname = current_database()
            where browser_role.rolname = current_user
            SQL);

        self::assertBrowserDatabaseIdentityAndRole([
            'driver' => $driver,
            ...(array) $identity,
        ]);
    }

    /**
     * @param  array<string, mixed>  $identity
     */
    public static function assertBrowserDatabaseIdentityAndRole(array $identity): void
    {
        if (
            ($identity['driver'] ?? null) !== 'pgsql'
            || ($identity['database_name'] ?? null) !== self::DATABASE
            || ($identity['current_role'] ?? null) !== self::ROLE
            || ($identity['session_role'] ?? null) !== self::ROLE
            || (int) ($identity['is_superuser'] ?? 1) !== 0
            || (int) ($identity['can_create_database'] ?? 1) !== 0
            || (int) ($identity['can_create_role'] ?? 1) !== 0
            || (int) ($identity['can_replicate'] ?? 1) !== 0
            || (int) ($identity['can_bypass_rls'] ?? 1) !== 0
            || (int) ($identity['inherits_memberships'] ?? 1) !== 0
            || (int) ($identity['can_login'] ?? 0) !== 1
            || (int) ($identity['owns_browser_database'] ?? 0) !== 1
            || (int) ($identity['can_create_in_browser_database'] ?? 0) !== 1
            || (int) ($identity['has_role_memberships'] ?? 1) !== 0
        ) {
            throw new RuntimeException('Browser testing database identity or least-privilege role properties failed verification.');
        }
    }
}
