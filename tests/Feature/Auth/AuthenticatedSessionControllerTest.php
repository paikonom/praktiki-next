<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_login_form(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Σύνδεση χειριστή');
    }

    public function test_user_can_log_in_with_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'operator',
            'password' => 'correct-password',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'operator',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_an_error_without_logging_in(): void
    {
        User::factory()->create([
            'username' => 'operator',
            'password' => 'correct-password',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'username' => 'operator',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertInvalid([
            'username' => 'Τα στοιχεία σύνδεσης δεν είναι σωστά.',
        ]);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'username' => 'unknown-user',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login'), [
            'username' => 'unknown-user',
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
