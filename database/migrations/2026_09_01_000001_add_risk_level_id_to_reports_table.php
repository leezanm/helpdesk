<?php

use Database\Seeders\RiskLevelSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Risk level on an aduan.
 *
 * Nullable column: a newly submitted aduan carries no risk level, and the
 * helpdesk officer must set one before forwarding it to a technical officer
 * (enforced in App\Livewire\Aduan\ReportShow).
 *
 * Existing aduan are backfilled to level 1 (Sangat Rendah) so historic records
 * are not left unclassified. The reference rows are seeded here first, because
 * on an existing database this migration runs before `db:seed`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('risk_level_id')
                ->nullable()
                ->after('report_category_id')
                ->constrained('_risk_levels')
                ->nullOnDelete();
        });

        // Seeder is idempotent; running it here guarantees level 1 exists
        // before the backfill below.
        (new RiskLevelSeeder())->run();

        // Trail status 11 — logged whenever the helpdesk sets/changes the risk
        // level. Only patched in for a database that already holds trail
        // statuses (staging/production), where the id must be set explicitly.
        // A fresh database is still empty here and gets the full set from
        // ReportTrailStatusSeeder instead, so its auto-increment ids stay 1..11.
        //
        // Deliberately does NOT depend on ids 9/10 being present: a dump taken
        // before the new flow only has 1..8, and those two rows arrive
        // separately via scripts/apply-flow-statuses.sql. Either order works.
        if (DB::table('_report_trail_statuses')->exists()
            && ! DB::table('_report_trail_statuses')->where('id', 11)->exists()) {
            DB::table('_report_trail_statuses')->insert([
                'id' => 11,
                'desc' => 'Tahap Risiko Ditetapkan',
                'class' => 'badge bg-dark',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $levelOneId = DB::table('_risk_levels')->where('level', 1)->value('id');

        if ($levelOneId) {
            DB::table('reports')->whereNull('risk_level_id')->update(['risk_level_id' => $levelOneId]);
        }
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['risk_level_id']);
            $table->dropColumn('risk_level_id');
        });
    }
};
