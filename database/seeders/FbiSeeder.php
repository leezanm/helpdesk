<?php

namespace Database\Seeders;

use App\Models\fbi;
use Illuminate\Database\Seeder;

class FbiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $slider = new fbi();
        $slider->rujukan = '001';
        $slider->tarikh = '6/1/2020';
        $slider->kepada = 'Cik Rosmah';
        $slider->jenis = 'Sekolah';
        $slider->perkara = 'Tempahan slot niaga bagi pegawai inspektorat';
        $slider->save();
    }
}
