<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\FeatureTestCase;

/**
 * Baseline test for the critical "login" path.
 *
 * Fortify is configured to authenticate against the `username` field
 * (see config/fortify.php), not email. This test locks in that behavior
 * so the Laravel 8 -> 12 upgrade cannot silently break it.
 *
 * Requirement 1 (task 1.2).
 */
class LoginTest extends FeatureTestCase
{
    public function test_user_can_login_with_username_and_password()
    {
        $user = User::factory()->create([
            'username' => 'officer1',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->post('/login', [
            'username' => 'officer1',
            'password' => 'secret-password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(); // Fortify redirects to home on success
    }

    public function test_login_fails_with_wrong_password()
    {
        User::factory()->create([
            'username' => 'officer1',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post('/login', [
            'username' => 'officer1',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create([
            'username' => 'officer1',
        ]);

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }
}
