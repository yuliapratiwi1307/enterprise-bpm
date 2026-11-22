<?php

namespace App\Services;

use App\Models\BpmRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class Prod04BrowserAccountProvisioner
{
    /**
     * @param  array<string, array{name: string, email: string, role_name: string}>  $accounts
     * @param  array<string, array{email: string, name: string, password: string}>  $credentials
     */
    public function provision(array $accounts, array $credentials): void
    {
        DB::transaction(function () use ($accounts, $credentials): void {
            DB::selectOne('SELECT pg_advisory_xact_lock(1347569492, 4)');

            foreach ($accounts as $roleCode => $account) {
                $credential = $credentials[$roleCode] ?? null;
                if (
                    ! is_array($credential)
                    || ($credential['email'] ?? null) !== $account['email']
                    || ($credential['name'] ?? null) !== $account['name']
                    || ! is_string($credential['password'] ?? null)
                ) {
                    throw new RuntimeException('The E2E account credential set is incomplete or invalid.');
                }

                $existing = User::query()->where('email', $account['email'])->first();
                if ($existing !== null && ! Hash::check($credential['password'], $existing->password)) {
                    throw new RuntimeException('An existing E2E account password does not match the private credential file; refusing to reset it.');
                }
                if (
                    $existing !== null
                    && $existing->roles()->where('code', '!=', $roleCode)->exists()
                ) {
                    throw new RuntimeException('An existing E2E account has additional roles; refusing to alter its permissions.');
                }

                $role = BpmRole::query()->firstOrCreate(
                    ['code' => $roleCode],
                    ['name' => $account['role_name']],
                );

                $user = $existing ?? User::query()->create([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $credential['password'],
                ]);

                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        });
    }
}
