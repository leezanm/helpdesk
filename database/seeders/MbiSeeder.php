<?php

namespace Database\Seeders;

use App\Models\mbi;
use Illuminate\Database\Seeder;

class MbiSeeder extends Seeder
{

    public function run()
    {
        $slider = new mbi();
        $slider->rujukan = '001';
        $slider->tarikh = '6/1/2020';
        $slider->kepada = 'Cik Sakinah';
        $slider->jenis = 'Sekolah 2';
        $slider->perkara = 'Tempahan slot niaga bagi pegawai';
        $slider->save();
    }
}
