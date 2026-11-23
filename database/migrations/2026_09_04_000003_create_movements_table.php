<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entri pergerakan: SATU aktiviti dengan julat tarikh, bukan satu baris per hari.
 *
 * Aktiviti berbilang hari dalam jadual sedia ada ("18 - 19 November",
 * "2 - 6 November") ialah satu aktiviti, jadi ia disimpan sebagai satu rekod
 * dan ditebarkan ke hari-hari dalam julatnya semasa grid bulanan dibina. Satu
 * rekod bermakna satu kali edit/hapus.
 *
 * Seorang pegawai boleh ada beberapa entri pada hari yang sama — tiada kekangan
 * unik pada tarikh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_officer_id')->constrained('monitored_officers');
            $table->foreignId('movement_type_id')->constrained('_movement_types');
            $table->string('title');                     // Tajuk
            $table->date('start_date');
            $table->date('end_date');                    // sama dengan start_date jika satu hari
            $table->time('start_time')->nullable();      // Masa
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();      // Lokasi / Tempat
            $table->text('remarks')->nullable();         // Catatan
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Grid bulanan menapis julat yang bertindih dengan satu bulan.
            $table->index(['start_date', 'end_date']);
            $table->index(['monitored_officer_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
