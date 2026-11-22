<?php

namespace Tests;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Foundation\Testing\CachedState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use PDO;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $this->traitsUsedByTest = class_uses_recursive(static::class);

        if (isset(CachedState::$cachedConfig, $this->traitsUsedByTest[WithCachedConfig::class])) {
            $this->markConfigCached($app);
        }

        if (isset(CachedState::$cachedRoutes, $this->traitsUsedByTest[WithCachedRoutes::class])) {
            $app->booting(fn () => $this->markRoutesCached($app));
        }

        $app->afterBootstrapping(
            LoadConfiguration::class,
            fn (Application $app) => $this->assertIsolatedPostgresTestDatabase($app),
        );

        $app->make(Kernel::class)->bootstrap();
        $this->applyPendingTestMigrations($app);

        return $app;
    }

    protected function setUpTraits(): array
    {
        $this->assertIsolatedPostgresTestDatabase($this->app);

        return parent::setUpTraits();
    }

    private function assertIsolatedPostgresTestDatabase(Application $app): void
    {
        $expectedDatabase = 'enterprise_bpm_test';
        $expectedRole = 'enterprise_bpm_test_user';
        $configuration = $app['config'];
        $connectionConfig = $configuration->get('database.connections.pgsql');
        $connections = $configuration->get('database.connections', []);

        if (
            $app->environment() !== 'testing'
            || $configuration->get('browser-testing.enabled') !== false
            || $configuration->get('database.default') !== 'pgsql'
            || ! is_array($connections)
            || ! array_key_exists('pgsql', $connections)
            || ! is_array($connectionConfig)
            || ($connectionConfig['driver'] ?? null) !== 'pgsql'
            || ($connectionConfig['host'] ?? null) !== '127.0.0.1'
            || (string) ($connectionConfig['port'] ?? '') !== '5432'
            || ($connectionConfig['database'] ?? null) !== $expectedDatabase
            || ($connectionConfig['username'] ?? null) !== $expectedRole
            || ! empty($connectionConfig['url'] ?? null)
            || $configuration->get('cache.default') !== 'array'
            || $configuration->get('session.driver') !== 'array'
            || $configuration->get('queue.default') !== 'sync'
            || $configuration->get('queue.batching.database') !== 'pgsql'
            || $configuration->get('queue.failed.database') !== 'pgsql'
        ) {
            throw new RuntimeException(
                'Tests require the sole PostgreSQL connection to be 127.0.0.1:5432/enterprise_bpm_test as enterprise_bpm_test_user, with non-database cache, session, and queue drivers.'
            );
        }

        foreach ($connections as $name => $connection) {
            if ($name === 'pgsql') {
                continue;
            }

            if (! is_array($connection)) {
                throw new RuntimeException('Tests cannot use malformed alternate database connection settings.');
            }

            if (
                ! empty($connection['url'] ?? null)
                || in_array($connection['database'] ?? null, ['enterprise_bpm', 'enterprise_bpm_browser'], true)
            ) {
                throw new RuntimeException(
                    'Tests cannot use alternate connection URLs or connections targeting protected databases.'
                );
            }
        }

        $this->assertTestDatabaseIdentityAndPrivileges($connectionConfig, $expectedDatabase, $expectedRole);

        $configuration->set('database.connections', ['pgsql' => $connectionConfig]);
    }

    private function applyPendingTestMigrations(Application $app): void
    {
        if ($app->environment('testing')) {
            $app->make(Migrator::class)->run(database_path('migrations'));
        }
    }

    /**
     * @param  array<string, mixed>  $connectionConfig
     */
    private function assertTestDatabaseIdentityAndPrivileges(
        array $connectionConfig,
        string $expectedDatabase,
        string $expectedRole,
    ): void {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $connectionConfig['host'],
            $connectionConfig['port'],
            $connectionConfig['database'],
        );

        $connection = new PDO(
            $dsn,
            $connectionConfig['username'],
            $connectionConfig['password'] ?? null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $identity = $connection->query(<<<'SQL'
            select
                current_database() as database_name,
                current_user as current_role,
                session_user as session_role,
                case when test_role.rolsuper then 1 else 0 end as role_is_superuser,
                case when test_role.rolcreatedb then 1 else 0 end as role_can_create_databases,
                case when test_role.rolcreaterole then 1 else 0 end as role_can_create_roles,
                case when test_role.rolreplication then 1 else 0 end as role_can_replicate,
                case when test_role.rolbypassrls then 1 else 0 end as role_can_bypass_rls,
                case when test_role.rolinherit then 1 else 0 end as role_inherits_memberships,
                case when test_database.datdba = test_role.oid then 1 else 0 end as owns_test_database,
                case when pg_has_role(test_role.oid, test_database.datdba, 'MEMBER') then 1 else 0 end as member_of_database_owner,
                case when has_database_privilege(test_role.oid, current_database(), 'CONNECT') then 1 else 0 end as can_connect_to_test_database,
                case when has_database_privilege(test_role.oid, current_database(), 'CREATE') then 1 else 0 end as can_create_in_test_database,
                case when has_database_privilege(test_role.oid, current_database(), 'TEMPORARY') then 1 else 0 end as can_create_temporary_objects,
                case when has_schema_privilege(test_role.oid, test_schema.oid, 'USAGE') then 1 else 0 end as can_use_public_schema,
                case when has_schema_privilege(test_role.oid, test_schema.oid, 'CREATE') then 1 else 0 end as can_create_in_public_schema,
                case when exists (
                    select 1
                    from pg_auth_members membership
                    where membership.member = test_role.oid
                ) then 1 else 0 end as has_role_memberships,
                case when exists (
                    select 1
                    from pg_class relation
                    where relation.relnamespace = test_schema.oid
                      and relation.relkind in ('r', 'p', 'v', 'm', 'S', 'f')
                      and relation.relowner <> test_role.oid
                ) then 1 else 0 end as has_relations_owned_by_another_role
            from pg_roles test_role
            join pg_database test_database on test_database.datname = current_database()
            join pg_namespace test_schema on test_schema.nspname = 'public'
            where test_role.rolname = current_user
            SQL)->fetch(PDO::FETCH_ASSOC);

        $connection = null;

        if (
            ! is_array($identity)
            || $identity['database_name'] !== $expectedDatabase
            || $identity['current_role'] !== $expectedRole
            || $identity['session_role'] !== $expectedRole
            || (int) $identity['role_is_superuser'] !== 0
            || (int) $identity['role_can_create_databases'] !== 0
            || (int) $identity['role_can_create_roles'] !== 0
            || (int) $identity['role_can_replicate'] !== 0
            || (int) $identity['role_can_bypass_rls'] !== 0
            || (int) $identity['role_inherits_memberships'] !== 0
            || (int) $identity['owns_test_database'] !== 1
            || (int) $identity['member_of_database_owner'] !== 1
            || (int) $identity['can_connect_to_test_database'] !== 1
            || (int) $identity['can_create_in_test_database'] !== 1
            || (int) $identity['can_create_temporary_objects'] !== 1
            || (int) $identity['can_use_public_schema'] !== 1
            || (int) $identity['can_create_in_public_schema'] !== 1
            || (int) $identity['has_role_memberships'] !== 0
            || (int) $identity['has_relations_owned_by_another_role'] !== 0
        ) {
            throw new RuntimeException(
                'PostgreSQL test database identity or privilege guard failed; refusing to run tests.'
            );
        }
    }
}
