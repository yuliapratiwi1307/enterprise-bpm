<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BrowserTestingRuntimeGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BrowserTestingSafetyGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_guard_allows_request_after_runtime_verification_succeeds(): void
    {
        $this->enableBrowserGuard();
        $this->usePassingBrowserTestingGuard();

        $this->get('/up')->assertOk();

        $this->assertSame('enterprise_bpm_test', config('database.connections.pgsql.database'));
        $this->assertSame('file', config('session.driver'));
        $this->assertSame(
            storage_path('framework/browser-testing/sessions'),
            config('session.files')
        );
        $this->assertSame(
            0,
            fileperms(storage_path('framework/browser-testing/sessions')) & 0077
        );
        $this->assertSame(
            storage_path('framework/browser-testing/files'),
            config('filesystems.disks.local.root')
        );
        $this->assertSame(
            0,
            fileperms(storage_path('framework/browser-testing/files')) & 0077
        );
    }

    public function test_file_session_keeps_login_authenticated_across_http_requests(): void
    {
        $this->enableBrowserGuard();
        $this->usePassingBrowserTestingGuard();

        $user = User::query()->create([
            'name' => 'Session Persistence Marketing',
            'email' => 'session-persistence@example.test',
            'password' => Hash::make('testing-only-password'),
        ]);
        $roleId = DB::table('bpm_roles')->where('code', 'MARKETING')->value('id');

        if ($roleId === null) {
            $roleId = DB::table('bpm_roles')->insertGetId([
                'code' => 'MARKETING',
                'name' => 'Marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('bpm_role_user')->insert([
            'user_id' => $user->id,
            'bpm_role_id' => $roleId,
        ]);

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'testing-only-password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Session Persistence Marketing')
            ->assertSee(route('prod02.index'));
    }

    public function test_global_guard_rejects_active_database_target_before_route(): void
    {
        config([
            'browser-testing.enabled' => true,
            'database.connections.pgsql.database' => 'enterprise_bpm',
        ]);

        $this->get('/up')
            ->assertStatus(503)
            ->assertSee('Browser testing safety verification failed.');
    }

    public function test_global_guard_rejects_non_isolated_cache_before_route(): void
    {
        config([
            'browser-testing.enabled' => true,
            'cache.default' => 'database',
        ]);

        $this->get('/up')
            ->assertStatus(503)
            ->assertSee('Browser testing safety verification failed.');
    }

    public function test_global_guard_rejects_session_directory_outside_browser_testing_storage(): void
    {
        config([
            'browser-testing.enabled' => true,
            'session.driver' => 'file',
            'session.files' => storage_path('framework/sessions'),
        ]);

        $this->get('/up')
            ->assertStatus(503)
            ->assertSee('Browser testing safety verification failed.');
    }

    public function test_global_guard_rejects_shared_local_upload_storage(): void
    {
        config([
            'browser-testing.enabled' => true,
            'filesystems.disks.local.root' => storage_path('shared-files'),
        ]);

        $this->get('/up')
            ->assertStatus(503)
            ->assertSee('Browser testing safety verification failed.');
    }

    private function enableBrowserGuard(): void
    {
        $connections = config('database.connections');
        unset($connections['active_database_guard']);
        DB::purge('active_database_guard');

        foreach ([
            storage_path('framework/browser-testing/sessions'),
            storage_path('framework/browser-testing/files'),
        ] as $directory) {
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                $this->fail('Could not create a private browser testing directory.');
            }

            if (! chmod($directory, 0700)) {
                $this->fail('Could not restrict browser testing directory permissions.');
            }
        }

        config([
            'browser-testing.enabled' => true,
            'database.connections' => $connections,
            'session.driver' => 'file',
            'session.files' => storage_path('framework/browser-testing/sessions'),
            'filesystems.disks.local.root' => storage_path('framework/browser-testing/files'),
        ]);
    }

    private function usePassingBrowserTestingGuard(): void
    {
        $this->app->instance(BrowserTestingRuntimeGuard::class, new class extends BrowserTestingRuntimeGuard
        {
            public function isExplicitlyEnabled(): bool
            {
                return true;
            }

            public function verify(): void {}
        });
    }
}
