<?php

namespace Database\Seeders;

use App\Models\MovementUnit;
use Illuminate\Database\Seeder;

class MovementUnitSeeder extends Seeder
{
    /**
     * Unit pertama yang menggunakan modul ini — unit yang sebelum ini memakai
     * Google Sheet "Jadual Pergerakan Unit Operasi dan Bantuan Teknikal, BPM".
     *
     * Roster pegawai TIDAK disemai: urus setia memasukkan nama melalui UI, jadi
     * tiada nama pegawai ditanam dalam repositori.
     *
     * Idempoten (firstOrCreate pada `name`).
     */
    public function run(): void
    {
        MovementUnit::firstOrCreate(
            ['name' => 'Unit Operasi dan Bantuan Teknikal'],
            ['short_name' => 'UOBT, BPM', 'is_active' => true]
        );
    }
}
