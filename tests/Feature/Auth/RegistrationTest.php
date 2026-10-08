<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered_without_an_id_upload(): void
    {
        $this->get('/register')
            ->assertStatus(200)
            ->assertDontSee('National ID')
            ->assertDontSee('name="barangay_id"')
            ->assertDontSee('Start camera');
    }

    public function test_new_users_can_register_without_submitting_a_verification_id(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'barangay' => 'Bangag',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertFalse($user->barangay_verified);
        $this->assertNull($user->barangay_id_path);
    }
}
