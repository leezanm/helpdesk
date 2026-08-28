<?php

namespace Tests\Feature\Livewire\Role;

use App\Livewire\Role\RoleIndex;
use App\Models\User;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class RoleIndexTest extends FeatureTestCase
{
    public function test_lists_seeded_roles_with_permissions()
    {
        // Base test case seeds RoleSeeder (jtk, pptm, ptm, pengguna,
        // helpdesk.admin, superadmin, pengurusan).
        Livewire::test(RoleIndex::class)
            ->assertOk()
            ->assertSee('superadmin')
            ->assertSee('helpdesk.admin')
            ->assertSee('pengguna');
    }

    public function test_search_filters_roles()
    {
        Livewire::test(RoleIndex::class)
            ->set('search', 'superadmin')
            ->assertSee('superadmin')
            ->assertDontSee('helpdesk.admin');
    }

    /**
     * Task 9.2: confirm spatie permission enforcement still works after the
     * Livewire conversion (the gates the routes/components rely on).
     */
    public function test_spatie_permissions_still_enforce()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        $this->assertTrue($admin->can('manage.user'));
        $this->assertTrue($admin->can('manage.category'));
        $this->assertFalse($admin->can('manage.backup')); // not granted to helpdesk.admin

        $ordinary = User::factory()->create(); // pengguna
        $this->assertTrue($ordinary->can('aduan.create'));
        $this->assertFalse($ordinary->can('manage.user'));
    }
}
