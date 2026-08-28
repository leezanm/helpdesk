<?php

namespace Tests\Feature\Livewire\User;

use App\Livewire\User\UserIndex;
use App\Models\User;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class UserIndexTest extends FeatureTestCase
{
    public function test_pengguna_scope_lists_ordinary_users_only()
    {
        // Factory users get the 'pengguna' role via UserObserver.
        $ordinary = User::factory()->create(['name' => 'Ali Biasa']);

        $admin = User::factory()->create(['name' => 'Siti Admin']);
        $admin->syncRoles(['helpdesk.admin']);

        Livewire::test(UserIndex::class, ['scope' => 'pengguna'])
            ->assertOk()
            ->assertSee('Ali Biasa')
            ->assertDontSee('Siti Admin');
    }

    public function test_search_filters_by_name()
    {
        User::factory()->create(['name' => 'Ahmad Zaki', 'username' => 'ahmad']);
        User::factory()->create(['name' => 'Fatimah', 'username' => 'fatimah']);

        Livewire::test(UserIndex::class, ['scope' => 'pengguna'])
            ->set('search', 'Ahmad')
            ->assertSee('Ahmad Zaki')
            ->assertDontSee('Fatimah');
    }

    public function test_deactivate_and_restore()
    {
        $user = User::factory()->create();

        Livewire::test(UserIndex::class)
            ->call('deactivate', $user->id);
        $this->assertFalse((bool) $user->fresh()->is_active);

        Livewire::test(UserIndex::class)
            ->call('restore', $user->id);
        $this->assertTrue((bool) $user->fresh()->is_active);
    }

    public function test_pentadbir_scope_lists_manage_user_holders()
    {
        $ordinary = User::factory()->create(['name' => 'Ali Biasa']);

        $admin = User::factory()->create(['name' => 'Admin Sistem']);
        $admin->syncRoles(['helpdesk.admin']); // has manage.user

        Livewire::test(UserIndex::class, ['scope' => 'pentadbir'])
            ->assertSee('Admin Sistem')
            ->assertDontSee('Ali Biasa');
    }

    public function test_title_reflects_scope()
    {
        Livewire::test(UserIndex::class, ['scope' => 'teknikal'])
            ->assertSet('title', 'Pegawai Teknikal');
    }
}
