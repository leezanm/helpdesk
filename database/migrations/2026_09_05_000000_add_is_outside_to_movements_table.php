<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lokasi aktiviti: dalam pejabat atau luar pejabat.
 *
 * Disimpan pada ENTRI dan bukan pada jenis pergerakan, kerana jenis yang sama
 * boleh berlaku di kedua-dua tempat: "Mesyuarat" di Bilik Mesyuarat Digitalis
 * Aras 11 berbeza daripada "Mesyuarat" di Putrajaya. Inilah yang membezakan
 * pegawai yang masih boleh dicari di pejabat daripada pegawai yang keluar.
 *
 * Nilai lalai `false` (dalam pejabat) selamat untuk mesyuarat dan perbincangan
 * dalaman yang membentuk sebahagian besar rekod. Entri sedia ada yang jelas di
 * luar — jenis "Kerja Luar" dan "Tugas Rasmi Luar Negara" — ditanda semula
 * dalam migration ini supaya data lama tidak tersalah kategori.
 */
return new class extends Migration
{
    /** Jenis yang maknanya memang di luar pejabat. */
    private const OUTSIDE_TYPES = ['Kerja Luar', 'Tugas Rasmi Luar Negara'];

    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->boolean('is_outside')->default(false)->after('location');
        });

        $outsideTypeIds = DB::table('_movement_types')
            ->whereIn('name', self::OUTSIDE_TYPES)
            ->pluck('id');

        if ($outsideTypeIds->isNotEmpty()) {
            DB::table('movements')
                ->whereIn('movement_type_id', $outsideTypeIds)
                ->update(['is_outside' => true]);
        }

        // Bengkel dan seminar ialah aktiviti yang sama dalam amalan unit, jadi
        // satu jenis sahaja diperlukan. Hanya dinamakan semula jika nama baharu
        // itu belum ada, supaya tiada pendua.
        $hasNewName = DB::table('_movement_types')->where('name', 'Bengkel / Seminar')->exists();

        if (! $hasNewName) {
            DB::table('_movement_types')
                ->where('name', 'Bengkel')
                ->update(['name' => 'Bengkel / Seminar']);
        }
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropColumn('is_outside');
        });

        DB::table('_movement_types')
            ->where('name', 'Bengkel / Seminar')
            ->update(['name' => 'Bengkel']);
    }
};
