<?php

namespace Tests;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReportStatusSeeder;
use Database\Seeders\ReportTrailStatusSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base class for feature tests that touch users.
 *
 * The application's UserObserver assigns the "pengguna" role on every user
 * creation, and roles depend on permissions. So the permission + role seeders
 * must run before any user is created. Seeding them here keeps the domain
 * behavior intact instead of working around it.
 */
abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(ReportStatusSeeder::class);
        $this->seed(ReportTrailStatusSeeder::class);
    }
}
