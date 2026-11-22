<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class Prod04E2ECredentialStore
{
    /**
     * @param  array<string, array{name: string, email: string, role_name: string}>  $accounts
     * @return array<string, array{email: string, name: string, password: string}>
     */
    public function generate(array $accounts): array
    {
        $credentials = [];
        foreach ($accounts as $role => $account) {
            $credentials[$role] = [
                'email' => $account['email'],
                'name' => $account['name'],
                'password' => bin2hex(random_bytes(32)),
            ];
        }

        return $credentials;
    }

    /**
     * @param  array<string, array{name: string, email: string, role_name: string}>  $accounts
     * @return array<string, array{email: string, name: string, password: string}>
     */
    public function read(string $path, array $accounts): array
    {
        $permissions = fileperms($path);
        if (is_link($path) || ! is_file($path) || $permissions === false || ($permissions & 0077) !== 0) {
            throw new RuntimeException('The existing E2E credential file is not a private regular file.');
        }

        $contents = file_get_contents($path);
        $credentials = $contents === false ? null : json_decode($contents, true);
        if (! is_array($credentials)) {
            throw new RuntimeException('The existing E2E credential file is invalid.');
        }
        if (array_keys($credentials) !== array_keys($accounts)) {
            throw new RuntimeException('The existing E2E credential file does not contain exactly the expected accounts.');
        }

        foreach ($accounts as $role => $account) {
            if (
                ! is_array($credentials[$role] ?? null)
                || ($credentials[$role]['email'] ?? null) !== $account['email']
                || ($credentials[$role]['name'] ?? null) !== $account['name']
                || ! is_string($credentials[$role]['password'] ?? null)
                || strlen($credentials[$role]['password']) !== 64
                || ! ctype_xdigit($credentials[$role]['password'])
            ) {
                throw new RuntimeException('The existing E2E credential file does not match the expected account set.');
            }
        }

        return $credentials;
    }

    /**
     * @param  array<string, array{email: string, name: string, password: string}>  $credentials
     */
    public function write(string $path, array $credentials): void
    {
        $previousUmask = umask(0077);
        $created = false;
        try {
            $handle = fopen($path, 'x');
            if ($handle === false) {
                throw new RuntimeException('Unable to create the private E2E credential file.');
            }
            $created = true;

            try {
                if (! chmod($path, 0600)) {
                    throw new RuntimeException('Unable to restrict permissions on the E2E credential file.');
                }
                $encoded = json_encode($credentials, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                $contents = $encoded.PHP_EOL;
                $written = 0;
                while ($written < strlen($contents)) {
                    $chunk = fwrite($handle, substr($contents, $written));
                    if ($chunk === false || $chunk === 0) {
                        throw new RuntimeException('Unable to persist the private E2E credential file.');
                    }
                    $written += $chunk;
                }
                if (! fflush($handle)) {
                    throw new RuntimeException('Unable to persist the private E2E credential file.');
                }
            } finally {
                fclose($handle);
            }
        } catch (Throwable $exception) {
            if ($created && is_file($path) && ! is_link($path)) {
                unlink($path);
            }

            throw $exception;
        } finally {
            umask($previousUmask);
        }

        $permissions = fileperms($path);
        if ($permissions === false || ($permissions & 0077) !== 0) {
            unlink($path);
            throw new RuntimeException('The E2E credential file permissions are not private.');
        }
    }
}
