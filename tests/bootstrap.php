<?php

$requiredEnvironment = [
    'APP_ENV' => 'testing',
    'BROWSER_TESTING_GUARD' => '0',
    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '5432',
    'DB_DATABASE' => 'enterprise_bpm_test',
    'DB_USERNAME' => 'enterprise_bpm_test_user',
    'DB_URL' => '',
    'DB_CACHE_CONNECTION' => 'pgsql',
    'DB_QUEUE_CONNECTION' => 'pgsql',
    'DB_SESSION_CONNECTION' => 'pgsql',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_CONNECTION' => 'pgsql',
    'SESSION_DRIVER' => 'array',
];

foreach ($requiredEnvironment as $name => $expectedValue) {
    if (getenv($name) !== $expectedValue) {
        fwrite(STDERR, "Unsafe PHPUnit environment: {$name} must be explicitly configured for the isolated test database.\n");
        exit(1);
    }
}

$testingEnvironmentFile = dirname(__DIR__).'/.env.testing';

if (! is_file($testingEnvironmentFile) || is_link($testingEnvironmentFile)) {
    fwrite(
        STDERR,
        "Missing local .env.testing file. Create it with credentials for enterprise_bpm_test; .env will not be used.\n"
    );
    exit(1);
}

$permissions = fileperms($testingEnvironmentFile);

if ($permissions === false || ($permissions & 0077) !== 0) {
    fwrite(STDERR, "The local .env.testing file must have owner-only permissions (for example, chmod 600).\n");
    exit(1);
}

require dirname(__DIR__).'/vendor/autoload.php';
