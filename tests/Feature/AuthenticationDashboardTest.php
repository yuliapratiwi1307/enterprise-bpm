<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_redirects_to_dashboard(): void
    {
        $user = $this->createUser();
        $this->get(route('prod02.index'))->assertRedirect(route('login'));

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = $this->createUser();
        $key = strtolower($user->email).'|127.0.0.1';
        RateLimiter::clear($key);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.post'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_dashboard_only_shows_production_for_marketing_role(): void
    {
        $marketingUser = $this->createUser('marketing@example.test');
        $this->grantMarketingRole($marketingUser);

        $this->actingAs($marketingUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('PROD-02')
            ->assertSee(route('prod02.index'));

        $this->get(route('prod02.index'))
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false);

        $otherUser = $this->createUser('other@example.test');

        $this->actingAs($otherUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('prod02.index'));
    }

    public function test_dashboard_is_default_authenticated_home_and_has_csrf_logout_form(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_logout_uses_post_and_ends_the_authenticated_session(): void
    {
        $this->actingAs($this->createUser());

        $this->post(route('logout'))
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_marketing_can_create_and_update_draft_with_audit_events(): void
    {
        $user = $this->createUser();
        $this->grantMarketingRole($user);
        $this->actingAs($user);

        $this->post(route('prod02.store'), [
            'client_name' => 'Contoso',
            'communication_source' => 'WhatsApp',
            'requirement_type' => 'Consultation',
            'description' => 'Initial request',
        ])->assertRedirect();

        $briefId = DB::table('client_briefs')->value('id');
        $this->assertDatabaseHas('client_briefs', [
            'id' => $briefId,
            'status' => 'DRAFT',
            'marketing_user_id' => $user->id,
            'client_name' => 'Contoso',
        ]);
        $this->assertDatabaseHas('client_brief_events', [
            'client_brief_id' => $briefId,
            'event_type' => 'DRAFT_CREATED',
            'actor_user_id' => $user->id,
        ]);

        $this->put(route('prod02.update', $briefId), [
            'client_name' => 'Contoso Updated',
            'communication_source' => 'Email',
            'requirement_type' => 'Consultation',
            'description' => 'Updated request',
        ])->assertRedirect();

        $this->assertDatabaseHas('client_briefs', [
            'id' => $briefId,
            'client_name' => 'Contoso Updated',
            'communication_source' => 'Email',
        ]);
        $this->assertDatabaseHas('client_brief_events', [
            'client_brief_id' => $briefId,
            'event_type' => 'DRAFT_UPDATED',
            'actor_user_id' => $user->id,
        ]);
        $this->assertSame(2, DB::table('client_brief_events')->where('client_brief_id', $briefId)->count());
    }

    public function test_non_marketing_user_cannot_access_production_routes(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('prod02.index'))
            ->assertForbidden();
    }

    private function createUser(string $email = 'user@example.test'): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('correct-password'),
        ]);
    }

    private function grantMarketingRole(User $user): void
    {
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
    }
}
