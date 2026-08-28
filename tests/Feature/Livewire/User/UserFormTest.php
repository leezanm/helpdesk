<?php

namespace Tests\Feature\Livewire\User;

use App\Livewire\User\UserForm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class UserFormTest extends FeatureTestCase
{
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

    public function test_edits_user_and_syncs_roles()
    {
        $user = User::factory()->create(['name' => 'Lama']);

        Livewire::test(UserForm::class)
            ->call('openEdit', $user->id)
            ->assertSet('name', 'Lama')
            ->set('name', 'Baru')
            ->set('roles', ['pengguna', 'helpdesk.admin'])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Baru', $user->name);
        $this->assertTrue($user->hasRole('helpdesk.admin'));
    }
}
