<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Prod04BrowserAccountProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class Prod04BrowserAccountProvisionerTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('PROD-04 tests require APP_ENV=testing.');
        }

        $identity = DB::selectOne('select current_database() as database, current_user as username');
        if (
            $identity?->database !== 'enterprise_bpm_test'
            || $identity?->username !== 'enterprise_bpm_test_user'
        ) {
            throw new RuntimeException('PROD-04 tests are restricted to enterprise_bpm_test_user on enterprise_bpm_test.');
        }
    }

    public function test_provisioning_is_idempotent_and_does_not_reset_existing_passwords(): void
    {
        $accounts = $this->accounts();
        $credentials = $this->credentials($accounts);
        $provisioner = app(Prod04BrowserAccountProvisioner::class);

        $provisioner->provision($accounts, $credentials);
        $firstUserIds = User::query()->whereIn('email', array_column($accounts, 'email'))->orderBy('id')->pluck('id')->all();
        $provisioner->provision($accounts, $credentials);

        $this->assertSame(3, User::query()->whereIn('email', array_column($accounts, 'email'))->count());
        $this->assertSame($firstUserIds, User::query()->whereIn('email', array_column($accounts, 'email'))->orderBy('id')->pluck('id')->all());
        $this->assertSame(3, DB::table('bpm_roles')->whereIn('code', array_keys($accounts))->count());

        foreach ($accounts as $roleCode => $account) {
            $user = User::query()->where('email', $account['email'])->firstOrFail();
            $this->assertTrue(Hash::check($credentials[$roleCode]['password'], $user->password));
            $this->assertSame([$roleCode], $user->roles()->pluck('code')->all());
        }
    }

    public function test_provisioning_refuses_to_attach_a_user_with_an_unexpected_role(): void
    {
        $accounts = $this->accounts();
        $credentials = $this->credentials($accounts);
        $existing = User::create([
            'name' => 'Existing E2E User',
            'email' => $accounts['ASST_MANAGER_PRODUKSI']['email'],
            'password' => Hash::make('an-existing-password'),
        ]);
        $credentials['ASST_MANAGER_PRODUKSI']['password'] = 'an-existing-password';
        $otherRoleId = DB::table('bpm_roles')->where('code', 'MARKETING')->value('id');
        if ($otherRoleId === null) {
            $otherRoleId = DB::table('bpm_roles')->insertGetId([
                'code' => 'MARKETING',
                'name' => 'Marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $existing->roles()->attach($otherRoleId);
        $originalPassword = $existing->fresh()->password;

        try {
            app(Prod04BrowserAccountProvisioner::class)->provision($accounts, $credentials);
            $this->fail('Provisioning must reject an account with an unexpected role.');
        } catch (RuntimeException) {
            $this->assertSame($originalPassword, $existing->fresh()->password);
        }
    }

    public function test_provisioning_refuses_to_reset_an_existing_password(): void
    {
        $accounts = $this->accounts();
        $credentials = $this->credentials($accounts);
        $existing = User::create([
            'name' => 'Existing E2E User',
            'email' => $accounts['ASST_MANAGER_PRODUKSI']['email'],
            'password' => Hash::make('an-existing-password'),
        ]);
        $originalPassword = $existing->fresh()->password;

        try {
            app(Prod04BrowserAccountProvisioner::class)->provision($accounts, $credentials);
            $this->fail('Provisioning must not reset an existing account password.');
        } catch (RuntimeException) {
            $this->assertSame($originalPassword, $existing->fresh()->password);
        }
        $this->assertSame($originalPassword, $existing->fresh()->password);
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

    /**
     * @param  array<string, array{name: string, email: string, role_name: string}>  $accounts
     * @return array<string, array{email: string, name: string, password: string}>
     */
    private function credentials(array $accounts): array
    {
        $credentials = [];
        foreach ($accounts as $role => $account) {
            $credentials[$role] = [
                'email' => $account['email'],
                'name' => $account['name'],
                'password' => str_repeat(substr(hash('sha256', $role), 0, 1), 64),
            ];
        }

        return $credentials;
    }
}
