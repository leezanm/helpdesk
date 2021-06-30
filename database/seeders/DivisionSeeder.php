<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $division = new Division();
        $division->desc = 'Division 1';
        $division->department_id = 1;
        $division->save();
    }
}
