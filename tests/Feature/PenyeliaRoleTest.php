<?php

namespace Tests\Feature;

use App\Livewire\User\UserForm;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Support\NotificationEvents;
use App\Support\NotificationRecipients;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\FeatureTestCase;

class PenyeliaRoleTest extends FeatureTestCase
{
    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['tel_no' => '0312345678']);
        $user->syncRoles([$role]);

        return $user->fresh();
    }

    public function test_role_labels_follow_the_helpdesk_structure()
    {
        $this->assertSame('Admin Sistem', Role::findByName('superadmin')->desc);
        $this->assertSame('Penyelia', Role::findByName('penyelia')->desc);
        $this->assertSame('Admin Helpdesk', Role::findByName('helpdesk.admin')->desc);
        $this->assertSame('Warga KKDW', Role::findByName('pengguna')->desc);
    }

    public function test_penyelia_holds_every_helpdesk_admin_permission_plus_user_and_role_management()
    {
        $penyelia = Role::findByName('penyelia');
        $helpdesk = Role::findByName('helpdesk.admin');

        foreach ($helpdesk->permissions->pluck('name') as $permission) {
            $this->assertTrue($penyelia->hasPermissionTo($permission), "penyelia kekurangan {$permission}");
        }

        $this->assertTrue($penyelia->hasPermissionTo('manage.user'));
        $this->assertTrue($penyelia->hasPermissionTo('manage.role'));
        $this->assertTrue($penyelia->hasPermissionTo('aduan.adminTask'));
        $this->assertFalse($penyelia->hasPermissionTo('manage.backup'));
        $this->assertFalse($penyelia->hasPermissionTo('manage.audit-trail'));
    }

    public function test_helpdesk_admin_can_no_longer_manage_users()
    {
        $this->assertFalse($this->userWithRole('helpdesk.admin')->can('manage.user'));
        $this->actingAs($this->userWithRole('helpdesk.admin'))->get('/pengguna')->assertForbidden();
        $this->actingAs($this->userWithRole('penyelia'))->get('/pengguna')->assertOk();
    }

    public function test_penyelia_can_open_main_dashboard_and_role_pages()
    {
        $penyelia = $this->userWithRole('penyelia');

        $this->actingAs($penyelia)->get('/dashboard/utama')->assertOk();
        $this->actingAs($penyelia)->get('/peranan')->assertOk();
        $this->actingAs($penyelia)->get('/tetapan/notifikasi')->assertOk();
    }

    public function test_penyelia_cannot_grant_admin_sistem()
    {
        $target = User::factory()->create();

        Livewire::actingAs($this->userWithRole('penyelia'))
            ->test(UserForm::class)
            ->call('openEdit', $target->id)
            ->set('roles', ['superadmin'])
            ->call('save')
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole('superadmin'));
    }

    public function test_penyelia_cannot_edit_an_admin_sistem_account()
    {
        $sysadmin = $this->userWithRole('superadmin');

        Livewire::actingAs($this->userWithRole('penyelia'))
            ->test(UserForm::class)
            ->call('openEdit', $sysadmin->id)
            ->set('roles', ['pengguna'])
            ->call('save')
            ->assertForbidden();

        $this->assertTrue($sysadmin->fresh()->hasRole('superadmin'));
    }

    public function test_penyelia_can_manage_a_normal_user()
    {
        $target = User::factory()->create(['tel_no' => '0312345678']);
        $target->syncRoles(['pengguna']);

        Livewire::actingAs($this->userWithRole('penyelia'))
            ->test(UserForm::class)
            ->call('openEdit', $target->id)
            ->set('roles', ['jtk'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($target->fresh()->hasRole('jtk'));
    }

    public function test_penyelia_is_a_default_recipient_of_helpdesk_notifications()
    {
        foreach ([
            NotificationEvents::ADUAN_DITERIMA_ADMIN,
            NotificationEvents::ADUAN_SELESAI_TINDAKAN,
            NotificationEvents::ADUAN_SELESAI_ADMIN,
        ] as $event) {
            $this->assertContains('penyelia', NotificationEvents::defaults()[$event]['roles']);
        }

        $penyelia = $this->userWithRole('penyelia');
        NotificationSetting::updateOrCreate(
            ['event_key' => NotificationEvents::ADUAN_DITERIMA_ADMIN],
            [
                'label' => 'x', 'enabled' => true, 'roles' => ['helpdesk.admin', 'penyelia'],
                'include_reporter' => false, 'include_assigned_officer' => false, 'extra_emails' => [],
            ]
        );

        $recipients = NotificationRecipients::for(NotificationEvents::ADUAN_DITERIMA_ADMIN, null);

        $this->assertTrue($recipients->contains(fn ($u) => $u->id === $penyelia->id));
    }

    public function test_migration_upgrades_an_existing_database()
    {
        // Pulihkan keadaan sebelum migrasi: tiada penyelia, label lama,
        // helpdesk.admin memegang manage.user.
        Role::findByName('penyelia')->delete();
        Role::findByName('superadmin')->update(['desc' => 'Superadmin']);
        Role::findByName('pengguna')->update(['desc' => 'Warga KPLB']);
        $helpdesk = Role::findByName('helpdesk.admin');
        $helpdesk->update(['desc' => 'Admin Sistem']);
        $helpdesk->givePermissionTo('manage.user');
        NotificationSetting::updateOrCreate(
            ['event_key' => NotificationEvents::ADUAN_SELESAI_ADMIN],
            [
                'label' => 'x', 'enabled' => true, 'roles' => ['helpdesk.admin'],
                'include_reporter' => false, 'include_assigned_officer' => false, 'extra_emails' => [],
            ]
        );

        $migration = require database_path('migrations/2026_09_06_000000_add_penyelia_role_and_rename_role_labels.php');
        $migration->up();
        $migration->up(); // selamat dijalankan berulang

        $this->assertSame('Admin Sistem', Role::findByName('superadmin')->desc);
        $this->assertSame('Warga KKDW', Role::findByName('pengguna')->desc);
        $this->assertSame('Admin Helpdesk', Role::findByName('helpdesk.admin')->desc);
        $this->assertFalse(Role::findByName('helpdesk.admin')->hasPermissionTo('manage.user'));
        $this->assertTrue(Role::findByName('helpdesk.admin')->hasPermissionTo('aduan.adminTask'));

        $penyelia = Role::findByName('penyelia');
        $this->assertTrue($penyelia->hasPermissionTo('manage.user'));
        $this->assertTrue($penyelia->hasPermissionTo('manage.role'));
        $this->assertTrue($penyelia->hasPermissionTo('aduan.adminTask'));

        $this->assertSame(
            ['helpdesk.admin', 'penyelia'],
            NotificationSetting::where('event_key', NotificationEvents::ADUAN_SELESAI_ADMIN)->first()->roles
        );
    }
}
