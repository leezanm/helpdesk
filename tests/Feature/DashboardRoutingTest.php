<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\FeatureTestCase;

class DashboardRoutingTest extends FeatureTestCase
{
    private function user(array $roles): User
    {
        $u = User::factory()->create(['tel_no' => '0312345678']);
        $u->syncRoles($roles);

        return $u;
    }

    // ---- Landing (/dashboard) is always personal ----

    public function test_officer_lands_on_officer_dashboard()
    {
        $res = $this->actingAs($this->user(['jtk']))->get('/dashboard');
        $res->assertOk();
        $res->assertSee('Dashboard Pegawai');
        $res->assertDontSee('Beban Tugas Pegawai'); // org-wide section
    }

    public function test_ordinary_user_lands_on_user_dashboard()
    {
        $res = $this->actingAs($this->user(['pengguna']))->get('/dashboard');
        $res->assertOk();
        $res->assertSee('Aduan Saya');
    }

    public function test_management_also_lands_on_personal_dashboard_first()
    {
        // helpdesk.admin has no aduan.updateTask -> personal = user dashboard
        $res = $this->actingAs($this->user(['helpdesk.admin']))->get('/dashboard');
        $res->assertOk();
        $res->assertDontSee('Beban Tugas Pegawai');
    }

    // ---- Main dashboard (/dashboard/utama) is role-gated ----

    public function test_management_roles_can_open_main_dashboard()
    {
        foreach (['helpdesk.admin', 'pengurusan', 'superadmin'] as $role) {
            $res = $this->actingAs($this->user([$role]))->get('/dashboard/utama');
            $res->assertOk();
            $res->assertSee('Beban Tugas Pegawai');
        }
    }

    public function test_officer_cannot_open_main_dashboard()
    {
        $this->actingAs($this->user(['jtk']))->get('/dashboard/utama')->assertForbidden();
    }

    public function test_ordinary_user_cannot_open_main_dashboard()
    {
        $this->actingAs($this->user(['pengguna']))->get('/dashboard/utama')->assertForbidden();
    }
}
