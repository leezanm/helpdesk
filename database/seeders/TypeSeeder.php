<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $type = new Type();
        $type->desc = 'Sekolah';
        $type->save();

        $type = new Type();
        $type->desc = 'Perubatan';
        $type->save();

        $type = new Type();
        $type->desc = 'KAT';
        $type->save();

        $type = new Type();
        $type->desc = 'BMTM';
        $type->save();
    }
}
