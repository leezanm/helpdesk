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

    public function test_management_roles_see_main_dashboard()
    {
        foreach (['helpdesk.admin', 'penyelia', 'pengurusan', 'superadmin'] as $role) {
            $res = $this->actingAs($this->user([$role]))->get('/dashboard');
            $res->assertOk();
            $res->assertSee('Beban Tugas Pegawai'); // management-only section
        }
    }

    public function test_officer_sees_officer_dashboard_not_main()
    {
        $res = $this->actingAs($this->user(['jtk']))->get('/dashboard');
        $res->assertOk();
        $res->assertSee('Dashboard Pegawai');
        $res->assertDontSee('Beban Tugas Pegawai');
    }

    public function test_ordinary_user_sees_simple_dashboard()
    {
        $res = $this->actingAs($this->user(['pengguna']))->get('/dashboard');
        $res->assertOk();
        $res->assertSee('Aduan Saya');
        $res->assertDontSee('Beban Tugas Pegawai');
    }
}
