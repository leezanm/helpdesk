<?php

namespace Database\Seeders;

use App\Models\ReportCategory;
use Illuminate\Database\Seeder;

class ReportCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $type = new ReportCategory();
        $type->desc = 'Aksesori Komputer';
        $type->save();

        $type = new ReportCategory();
        $type->desc = 'Perisian Komputer';
        $type->save();

        $type = new ReportCategory();
        $type->desc = 'Komputer Meja (Desktop)';
        $type->save();
    }
}
