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
        $type->desc = 'Komputer Riba (Notebook)';
        $type->save();

        $type = new Type();
        $type->desc = 'Komputer Meja (Desktop)';
        $type->save();

        $type = new Type();
        $type->desc = 'Peri sian Komputer';
        $type->save();

        $type = new Type();
        $type->desc = 'Printer';
        $type->save();
    }
}
