<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unit/kumpulan yang mempunyai jadual pergerakan sendiri.
 *
 * Modul ini bermula dengan satu unit sahaja (Unit Operasi dan Bantuan Teknikal,
 * BPM) yang sebelum ini menggunakan Google Sheet. Unit disimpan sebagai jadual
 * dan bukan tetapan tetap supaya bahagian lain boleh menggunakan modul yang
 * sama kemudian tanpa migration baharu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // "Unit Operasi dan Bantuan Teknikal"
            $table->string('short_name')->nullable();     // "UOBT, BPM"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_units');
    }
};
