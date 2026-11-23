<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jenis pergerakan (Mesyuarat, Kerja Luar, Bengkel, Kursus, Cuti Rehat, ...).
 *
 * Jadual rujukan — ikut konvensyen awalan `_` seperti `_report_statuses` dan
 * `_risk_levels` supaya ia boleh diselenggara dari UI tanpa perubahan kod.
 *
 * `is_leave` menandakan jenis cuti: ia asas kepada amaran apabila satu aktiviti
 * bertindih dengan cuti pegawai yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('_movement_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();               // "Mesyuarat"
            $table->string('class')->default('bg-secondary'); // kelas lencana pada grid
            $table->boolean('is_leave')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('_movement_types');
    }
};
