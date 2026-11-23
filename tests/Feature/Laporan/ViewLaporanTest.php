<?php

namespace Tests\Feature\Laporan;

use App\Models\User;
use Tests\FeatureTestCase;

/**
 * Baseline test for the critical "view laporan (report/analytics)" path.
 *
 * Locks in that the laporan landing page and the "by status" aggregate view
 * render for an authenticated user. The aggregate query uses GROUP BY, which
 * is the area flagged for MySQL 8 ONLY_FULL_GROUP_BY review (task 2.3).
 * Requirement 1 (task 1.2).
 */
class ViewLaporanTest extends FeatureTestCase
{
    public function test_laporan_landing_page_renders()
    {
        // Laporan is org-wide — requires a management role (access-main-dashboard).
        $user = User::factory()->create();
        $user->syncRoles(['helpdesk.admin']);

        $response = $this->actingAs($user)->get('/laporan');

        $response->assertOk();
    }

    public function test_laporan_by_status_renders()
    {
        $user = User::factory()->create();
        $user->syncRoles(['helpdesk.admin']);

        $response = $this->actingAs($user)->get('/laporan/mengikut-status-tindakan');

        $response->assertOk();
    }

    public function test_laporan_by_risk_level_renders()
    {
        $user = User::factory()->create();
        $user->syncRoles(['helpdesk.admin']);

        $response = $this->actingAs($user)->get('/laporan/mengikut-tahap-risiko');

        $response->assertOk();
        $response->assertSee('Tahap 5: Sangat Tinggi');
        // Aduan not yet classified are reported as their own bucket.
        $response->assertSee('Belum Ditetapkan');
    }

    public function test_laporan_by_risk_level_api_returns_every_level()
    {
        $user = User::factory()->create();
        $user->syncRoles(['helpdesk.admin']);

        $response = $this->actingAs($user)->postJson('/api/laporan/by-risk');

        $response->assertOk()
            ->assertJsonPath('success', true)
            // 5 levels + the "Belum Ditetapkan" bucket
            ->assertJsonCount(6, 'laporan');
    }

    public function test_guest_cannot_view_laporan()
    {
        $response = $this->get('/laporan');

        $response->assertRedirect('/login');
    }
}
