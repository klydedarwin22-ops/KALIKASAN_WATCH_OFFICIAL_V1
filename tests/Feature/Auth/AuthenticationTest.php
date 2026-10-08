<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_officer_is_marked_online_after_successful_login(): void
    {
        $officer = User::factory()->create([
            'role' => 'officer',
            'is_online' => false,
        ]);

        $this->post('/login', [
            'email' => $officer->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertTrue($officer->fresh()->is_online);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_officer_is_marked_offline_after_logout(): void
    {
        $officer = User::factory()->create([
            'role' => 'officer',
            'is_online' => true,
        ]);

        $this->actingAs($officer)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertFalse($officer->fresh()->is_online);
    }
}
