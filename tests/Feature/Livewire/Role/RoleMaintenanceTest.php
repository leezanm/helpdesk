<?php

namespace Tests\Feature\Livewire\Role;

use App\Livewire\Role\RoleForm;
use App\Livewire\Role\RoleIndex;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\FeatureTestCase;

class RoleMaintenanceTest extends FeatureTestCase
{
    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['tel_no' => '0312345678']);
        $user->syncRoles([$role]);

        return $user->fresh();
    }

    public function test_roles_pages_need_manage_role()
    {
        $this->actingAs($this->userWithRole('helpdesk.admin'))->get('/peranan')->assertForbidden();
        $this->actingAs($this->userWithRole('pengguna'))->get('/peranan')->assertForbidden();
        $this->actingAs($this->userWithRole('penyelia'))->get('/peranan')->assertOk();
    }

    public function test_component_actions_need_manage_role()
    {
        $admin = $this->userWithRole('helpdesk.admin');

        Livewire::actingAs($admin)->test(RoleForm::class)
            ->set('name', 'baharu')->set('desc', 'Baharu')
            ->call('save')->assertForbidden();

        $this->assertNull(Role::where('name', 'baharu')->first());
    }

    public function test_penyelia_creates_a_role_with_permissions_they_hold()
    {
        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleForm::class)
            ->call('openCreate')
            ->set('name', 'penyelia.kanan')
            ->set('desc', 'Penyelia Kanan')
            ->set('permissions', ['aduan.all', 'view.report'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('role-saved');

        $role = Role::findByName('penyelia.kanan');
        $this->assertSame('Penyelia Kanan', $role->desc);
        $this->assertEqualsCanonicalizing(['aduan.all', 'view.report'], $role->permissions->pluck('name')->all());
    }

    public function test_create_validates_name_and_desc()
    {
        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleForm::class)
            ->call('openCreate')
            ->set('name', 'Nama Salah!')->set('desc', '')
            ->call('save')
            ->assertHasErrors(['name', 'desc']);

        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleForm::class)
            ->call('openCreate')
            ->set('name', 'jtk')->set('desc', 'Pendua')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_label_edit_keeps_name_and_updates_permissions()
    {
        $pengguna = Role::findByName('pengguna');

        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleForm::class)
            ->call('openEdit', $pengguna->id)
            ->assertSet('name', 'pengguna')
            ->set('name', 'diubah') // diabaikan: nama kod tidak berubah
            ->set('desc', 'Warga KPLB/KKDW')
            ->call('save');

        $pengguna->refresh();
        $this->assertSame('pengguna', $pengguna->name);
        $this->assertSame('Warga KPLB/KKDW', $pengguna->desc);
    }

    public function test_penyelia_cannot_grant_permissions_they_do_not_hold()
    {
        $role = Role::create(['name' => 'tambahan', 'guard_name' => 'web', 'desc' => 'Tambahan']);

        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleForm::class)
            ->call('openEdit', $role->id)
            ->set('permissions', ['aduan.all', 'manage.backup', 'manage.audit-trail', 'manage.admin'])
            ->call('save');

        $this->assertSame(['aduan.all'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_penyelia_leaves_permissions_they_do_not_hold_untouched_on_a_role()
    {
        $role = Role::create(['name' => 'tambahan', 'guard_name' => 'web', 'desc' => 'Tambahan']);
        $role->givePermissionTo(['manage.backup', 'aduan.all']);

        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleForm::class)
            ->call('openEdit', $role->id)
            ->set('permissions', []) // cuba kosongkan semuanya
            ->call('save');

        $this->assertSame(['manage.backup'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_superadmin_may_grant_any_permission()
    {
        $role = Role::create(['name' => 'tambahan', 'guard_name' => 'web', 'desc' => 'Tambahan']);

        Livewire::actingAs($this->userWithRole('superadmin'))->test(RoleForm::class)
            ->call('openEdit', $role->id)
            ->set('permissions', ['manage.backup'])
            ->call('save');

        $this->assertTrue($role->fresh()->hasPermissionTo('manage.backup'));
    }

    public function test_penyelia_cannot_open_or_change_the_admin_sistem_role()
    {
        $superadmin = Role::findByName('superadmin');
        $before = $superadmin->permissions->count();

        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleForm::class)
            ->call('openEdit', $superadmin->id)
            ->assertForbidden();

        $this->assertSame($before, $superadmin->fresh()->permissions->count());
    }

    public function test_admin_sistem_role_keeps_every_permission_but_label_is_editable_by_superadmin()
    {
        $superadmin = Role::findByName('superadmin');
        $total = \Spatie\Permission\Models\Permission::count();

        Livewire::actingAs($this->userWithRole('superadmin'))->test(RoleForm::class)
            ->call('openEdit', $superadmin->id)
            ->set('desc', 'Admin Sistem (Developer)')
            ->set('permissions', [])
            ->call('save');

        $superadmin->refresh();
        $this->assertSame('Admin Sistem (Developer)', $superadmin->desc);
        $this->assertSame($total, $superadmin->permissions->count());
    }

    public function test_delete_custom_role_without_users()
    {
        $role = Role::create(['name' => 'tambahan', 'guard_name' => 'web', 'desc' => 'Tambahan']);

        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleIndex::class)
            ->call('delete', $role->id)
            ->assertSet('error', null);

        $this->assertNull(Role::where('name', 'tambahan')->first());
    }

    public function test_cannot_delete_a_role_still_held_by_users()
    {
        $role = Role::create(['name' => 'tambahan', 'guard_name' => 'web', 'desc' => 'Tambahan']);
        User::factory()->create()->assignRole('tambahan');

        Livewire::actingAs($this->userWithRole('penyelia'))->test(RoleIndex::class)
            ->call('delete', $role->id)
            ->assertSet('error', 'Peranan masih dipegang oleh pengguna. Tukar peranan mereka dahulu.');

        $this->assertNotNull(Role::where('name', 'tambahan')->first());
    }

    public function test_cannot_delete_system_roles()
    {
        foreach (['superadmin', 'penyelia', 'helpdesk.admin', 'pengurusan', 'jtk', 'ptm', 'pptm', 'pengguna'] as $name) {
            Livewire::actingAs($this->userWithRole('superadmin'))->test(RoleIndex::class)
                ->call('delete', Role::findByName($name)->id)
                ->assertSet('error', 'Peranan sistem tidak boleh dipadam.');

            $this->assertNotNull(Role::where('name', $name)->first(), $name);
        }
    }

    public function test_delete_needs_manage_role()
    {
        $role = Role::create(['name' => 'tambahan', 'guard_name' => 'web', 'desc' => 'Tambahan']);

        Livewire::actingAs($this->userWithRole('helpdesk.admin'))->test(RoleIndex::class)
            ->call('delete', $role->id)
            ->assertForbidden();

        $this->assertNotNull(Role::where('name', 'tambahan')->first());
    }

    public function test_new_role_is_assignable_to_users()
    {
        Role::create(['name' => 'tambahan', 'guard_name' => 'web', 'desc' => 'Tambahan']);

        Livewire::actingAs($this->userWithRole('penyelia'))->test(\App\Livewire\User\UserForm::class)
            ->call('openCreate')
            ->set('name', 'Orang Baru')->set('username', 'orangbaru')->set('email', 'ob@example.test')
            ->set('roles', ['tambahan'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(User::where('username', 'orangbaru')->first()->hasRole('tambahan'));
    }
}
