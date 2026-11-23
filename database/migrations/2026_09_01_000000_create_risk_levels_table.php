<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risk level (tahap risiko) reference table for aduan. Five seeded levels
 * (1 Sangat Rendah .. 5 Sangat Tinggi) assigned by the helpdesk officer.
 *
 * Kept as its own table (same convention as `_report_statuses`) so the label,
 * criteria text and badge colour can be maintained from the UI without a code
 * change. `level` is the stable numeric scale; `desc` / `reference` are the
 * editable display texts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('_risk_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();  // 1..5, system-fixed
            $table->string('desc');                          // e.g. "Sangat Rendah"
            $table->text('reference');                       // kriteria penuh
            $table->string('class');                         // badge class
            $table->boolean('is_active')->default(true);     // only active ones selectable
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('_risk_levels');
    }
};
