<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            // DepartmentSeeder::class,
            // DivisionSeeder::class,
            UserSeeder::class,
            ReportStatusSeeder::class,
            RiskLevelSeeder::class,
            ReportCategorySeeder::class,
            ReportTrailStatusSeeder::class,
            NotificationSettingSeeder::class,
            MovementUnitSeeder::class,
            MovementTypeSeeder::class,
        ]);
    }
}
