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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/laporan');

        $response->assertOk();
    }

    public function test_laporan_by_status_renders()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/laporan/mengikut-status-tindakan');

        $response->assertOk();
    }

    public function test_guest_cannot_view_laporan()
    {
        $response = $this->get('/laporan');

        $response->assertRedirect('/login');
    }
}
