<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuti umum (contoh: Deepavali) yang ditandakan pada jadual bulanan.
 *
 * Satu rekod untuk semua pegawai — dalam Google Sheet cuti umum ditulis
 * berulang pada setiap baris pegawai. `movement_unit_id` null bermaksud cuti
 * itu terpakai kepada semua unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // "Deepavali"
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('movement_unit_id')->nullable()->constrained('movement_units');
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_holidays');
    }
};
