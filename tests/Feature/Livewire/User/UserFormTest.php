<?php

namespace Tests\Feature\Livewire\User;

use App\Livewire\User\UserForm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class UserFormTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // User management requires manage.user.
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage.user');
        $this->actingAs($admin);
    }

    public function test_create_validates_required_fields()
    {
        Livewire::test(UserForm::class)
            ->call('openCreate')
            ->set('name', '')
            ->set('username', '')
            ->set('email', '')
            ->set('roles', [])
            ->call('save')
            ->assertHasErrors(['name', 'username', 'email', 'roles']);
    }

    public function test_creates_user_with_roles_and_default_password()
    {
        Livewire::test(UserForm::class)
            ->call('openCreate')
            ->set('name', 'Baharu Pengguna')
            ->set('username', 'baharu')
            ->set('email', 'baharu@example.test')
            ->set('roles', ['pengguna'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-saved');

        $user = User::where('username', 'baharu')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('pengguna'));
        $this->assertTrue(Hash::check('pass1234', $user->password));
    }

    public function test_username_must_be_unique_on_create()
    {
        User::factory()->create(['username' => 'existing']);

        Livewire::test(UserForm::class)
            ->call('openCreate')
            ->set('name', 'X')
            ->set('username', 'existing')
            ->set('email', 'x@example.test')
            ->set('roles', ['pengguna'])
            ->call('save')
            ->assertHasErrors(['username' => 'unique']);
    }

    public function test_edits_user_updates_only_phone_address_and_roles()
    {
        // On edit, AD-owned fields (name/email/etc.) must NOT change even if
        // tampered; only phone, address and access (roles) are updatable.
        $user = User::factory()->create([
            'name' => 'Lama',
            'tel_no' => '0311112222',
            'address' => 'Alamat Lama',
        ]);

        Livewire::test(UserForm::class)
            ->call('openEdit', $user->id)
            ->assertSet('name', 'Lama')
            ->set('name', 'Baru')                    // attempt to change AD field
            ->set('tel_no', '0388889999')
            ->set('address', 'Alamat Baru')
            ->set('roles', ['pengguna', 'helpdesk.admin'])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Lama', $user->name);     // unchanged (from AD)
        $this->assertEquals('0388889999', $user->tel_no);
        $this->assertEquals('Alamat Baru', $user->address);
        $this->assertTrue($user->hasRole('helpdesk.admin'));
    }
}
