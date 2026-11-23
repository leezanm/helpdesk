<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phone number for the "bagi pihak" block of an aduan.
 *
 * Follows the same convention as the other bp_* columns: when the represented
 * person has a local user record the block holds the acting wakil's details
 * (their phone lands here, the represented person's phone is written to
 * users.tel_no); when the represented person is AD-only there is no user
 * record to write to, so their phone is kept here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('bp_tel_no', 30)->nullable()->after('bp_email');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('bp_tel_no');
        });
    }
};
