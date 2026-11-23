<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\FeatureTestCase;

class ResetDemoPasswordTest extends FeatureTestCase
{
    private function user(string $username, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'username' => $username,
            'password' => Hash::make('original-secret'),
        ], $attributes));

        return $user;
    }

    public function test_resets_password_for_named_accounts_only()
    {
        $target = $this->user('akram.zaki');
        $other = $this->user('someone.else');

        $this->artisan('demo:reset-password akram.zaki --force')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('test1234', $target->fresh()->password));
        // Untouched.
        $this->assertTrue(Hash::check('original-secret', $other->fresh()->password));
    }

    public function test_fills_empty_phone_so_the_phone_gate_does_not_block_the_demo()
    {
        $target = $this->user('akram.zaki', ['tel_no' => null]);

        $this->artisan('demo:reset-password akram.zaki --force')->assertSuccessful();

        $this->assertNotEmpty($target->fresh()->tel_no);
    }

    public function test_keeps_an_existing_phone_number()
    {
        $target = $this->user('akram.zaki', ['tel_no' => '019-1234567']);

        $this->artisan('demo:reset-password akram.zaki --force')->assertSuccessful();

        $this->assertEquals('019-1234567', $target->fresh()->tel_no);
    }

    public function test_skip_phone_option_leaves_tel_no_empty()
    {
        $target = $this->user('akram.zaki', ['tel_no' => null]);

        $this->artisan('demo:reset-password akram.zaki --skip-phone --force')->assertSuccessful();

        $this->assertEmpty($target->fresh()->tel_no);
    }

    public function test_custom_password_is_honoured()
    {
        $target = $this->user('akram.zaki');

        $this->artisan('demo:reset-password akram.zaki --password=demo9999 --force')->assertSuccessful();

        $this->assertTrue(Hash::check('demo9999', $target->fresh()->password));
    }

    public function test_activate_option_reactivates_the_account()
    {
        $target = $this->user('nizam.bakar');
        $target->is_active = false;
        $target->save();

        $this->artisan('demo:reset-password nizam.bakar --activate --force')->assertSuccessful();

        $this->assertTrue((bool) $target->fresh()->is_active);
    }

    public function test_unknown_username_fails_without_touching_anything()
    {
        $target = $this->user('akram.zaki');

        $this->artisan('demo:reset-password akram.zaki tiada.akaun --force')
            ->expectsOutputToContain('tiada.akaun')
            ->assertFailed();

        $this->assertTrue(Hash::check('original-secret', $target->fresh()->password));
    }

    public function test_declining_the_confirmation_changes_nothing()
    {
        $target = $this->user('akram.zaki');

        $this->artisan('demo:reset-password akram.zaki')
            ->expectsConfirmation('Set semula kata laluan 1 akaun di atas kepada "test1234"?', 'no')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('original-secret', $target->fresh()->password));
    }

    public function test_refuses_to_run_in_production()
    {
        app()['env'] = 'production';
        $target = $this->user('akram.zaki');

        $this->artisan('demo:reset-password akram.zaki --force')->assertFailed();

        $this->assertTrue(Hash::check('original-secret', $target->fresh()->password));
    }

    public function test_reset_account_can_log_in_with_the_demo_password()
    {
        $this->user('akram.zaki');

        $this->artisan('demo:reset-password akram.zaki --force')->assertSuccessful();

        $response = $this->post('/login', ['username' => 'akram.zaki', 'password' => 'test1234']);

        $response->assertRedirect();
        $this->assertAuthenticatedAs(User::where('username', 'akram.zaki')->first());
    }
}
