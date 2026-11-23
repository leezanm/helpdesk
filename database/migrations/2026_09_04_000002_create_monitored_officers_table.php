<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Senarai pegawai yang dipantau (roster) bagi satu unit.
 *
 * Pemantauan adalah TERPILIH: bukan semua pengguna sistem dipantau, jadi
 * keahlian disimpan di sini dan bukan sebagai penanda pada jadual `users`.
 * Baris ini juga memegang susunan paparan (lajur BIL dalam Google Sheet) dan
 * nama paparan bergelar ("En. ..." / "Pn. ...").
 *
 * `display_name` ialah snapshot: jadual bulan-bulan lepas kekal betul walaupun
 * pengguna bertukar nama atau di-soft-delete daripada sistem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_unit_id')->constrained('movement_units');
            $table->foreignId('user_id')->constrained('users');
            $table->string('display_name');
            $table->unsignedInteger('sort_order')->default(0);
            // Nyahaktif, bukan hapus: entri pergerakan lepas kekal utuh.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Seorang pegawai hanya sekali dalam satu unit.
            $table->unique(['movement_unit_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_officers');
    }
};
