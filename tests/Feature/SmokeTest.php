<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Baseline smoke tests — establish that the app boots, the schema migrates,
 * and the primary public routes respond before the framework upgrade begins.
 *
 * These are intentionally lightweight so they remain valid across the
 * Laravel 8 -> 12 upgrade steps (Requirement 1, task 1.2).
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    /** The full migration set applies cleanly on a fresh database. */
    public function test_database_migrates_cleanly()
    {
        // RefreshDatabase already ran the migrations by the time we get here.
        // Assert a representative table from each domain area exists.
        $this->assertTrue(\Schema::hasTable('users'));
        $this->assertTrue(\Schema::hasTable('reports'));
        $this->assertTrue(\Schema::hasTable('report_categories'));
        $this->assertTrue(\Schema::hasTable('_report_statuses'));
        $this->assertTrue(\Schema::hasTable('permissions'));
        $this->assertTrue(\Schema::hasTable('notifications'));
    }

    /** The root path redirects unauthenticated users to the login screen. */
    public function test_root_redirects_to_login()
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /** The login screen renders (Fortify login view). */
    public function test_login_screen_renders()
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    /** Protected routes require authentication. */
    public function test_dashboard_requires_authentication()
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
