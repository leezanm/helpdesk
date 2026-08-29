<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema change for the new aduan flow (see .kiro/specs/ui-refresh/flow baru.md):
 * add lesson-learn columns to report_trails.
 *
 * The report STATUS reference data (rename 2/3, add 7 "Selesai Tindakan" and
 * 8 "Tindakan Lanjut Diperlukan") lives in ReportStatusSeeder / the trail
 * seeder for fresh databases. For the existing staging/production database the
 * same data change is applied once via scripts/apply-flow-statuses.sql (a data
 * update, not a schema migration — keeping this migration reseed-safe).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_trails', function (Blueprint $table) {
            if (! Schema::hasColumn('report_trails', 'punca_masalah')) {
                $table->text('punca_masalah')->nullable()->after('technical_remark');
            }
            if (! Schema::hasColumn('report_trails', 'langkah_tindakan')) {
                $table->text('langkah_tindakan')->nullable()->after('punca_masalah');
            }
            if (! Schema::hasColumn('report_trails', 'catatan_teknikal')) {
                $table->text('catatan_teknikal')->nullable()->after('langkah_tindakan');
            }
            if (! Schema::hasColumn('report_trails', 'kenapa_lanjut')) {
                $table->text('kenapa_lanjut')->nullable()->after('catatan_teknikal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_trails', function (Blueprint $table) {
            $table->dropColumn(['punca_masalah', 'langkah_tindakan', 'catatan_teknikal', 'kenapa_lanjut']);
        });
    }
};
