<?php

namespace Tests\Feature\Aduan;

use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\RiskClassifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\FeatureTestCase;

/**
 * Keyword/category risk classification used to backfill historic aduan.
 */
class ClassifyReportRiskTest extends FeatureTestCase
{
    private function classifier(): RiskClassifier
    {
        return new RiskClassifier();
    }

    /** Category ids follow report_categories in production (6 = Rangkaian). */
    public function test_category_sets_the_baseline_level()
    {
        $c = $this->classifier();

        $this->assertEquals(1, $c->classify(9, 'Permohonan pautan mesyuarat'));   // Acara/Mesyuarat
        $this->assertEquals(2, $c->classify(4, 'Mohon bantuan teknikal'));        // Pencetak
        $this->assertEquals(3, $c->classify(6, 'Mohon bantuan teknikal'));        // Rangkaian
        $this->assertEquals(4, $c->classify(11, 'Mohon bantuan teknikal'));       // Pelayan
    }

    public function test_unknown_category_falls_back_to_level_one()
    {
        $this->assertEquals(1, $this->classifier()->classify(null, ''));
        $this->assertEquals(1, $this->classifier()->classify(999, ''));
    }

    public function test_security_wording_in_subject_raises_to_level_five()
    {
        $c = $this->classifier();

        $this->assertEquals(5, $c->classify(3, 'PC kena hacked'));
        $this->assertEquals(5, $c->classify(7, 'Emel phishing diterima'));
        $this->assertEquals(5, $c->classify(10, 'Kebocoran data sistem'));
    }

    public function test_incident_wording_raises_to_level_four()
    {
        $c = $this->classifier();

        $this->assertEquals(4, $c->classify(3, 'Komputer ada virus'));
        $this->assertEquals(4, $c->classify(6, 'Semua pengguna tidak boleh akses'));
        // Found in the description too, not just the subject.
        $this->assertEquals(4, $c->classify(3, 'Blue Screen PC', 'Masalah crowdstrike pada PC pegawai'));
    }

    public function test_routine_request_descriptions_do_not_inflate_the_level()
    {
        $c = $this->classifier();

        // Meeting requests mention "dalam talian", dates and venues; they stay
        // at the category baseline.
        $this->assertEquals(1, $c->classify(
            9,
            'PERMOHONAN PAUTAN MESYUARAT PENYELARASAN',
            "Tarikh: 30 Januari 2023 (Isnin)\r\nMasa: 2.30 petang\r\nMesyuarat secara dalam talian"
        ));

        // A software request that merely cites leakage risk as justification is
        // not a leak incident.
        $this->assertEquals(2, $c->classify(
            2,
            'Permohonan Install Aplikasi Adobe Acrobat Pro',
            'Alternatif lain hanya laman mengedit PDF online. Ini menyebabkan kemungkinan berlakunya kebocoran maklumat.'
        ));
    }

    public function test_level_is_never_lowered_below_the_category_baseline()
    {
        // "install" is a level-2 keyword but the category baseline is 4.
        $this->assertEquals(4, $this->classifier()->classify(11, 'Install pelayan baharu'));
    }

    // ===================== command =====================

    /**
     * The classifier's baseline map is keyed by the production category ids,
     * so the row must carry that exact id. ReportCategory guards `id` against
     * mass assignment, hence the explicit attribute set.
     */
    private function ensureCategory(int $id, string $desc): void
    {
        if (ReportCategory::whereKey($id)->exists()) {
            return;
        }

        $category = new ReportCategory();
        $category->id = $id;
        $category->desc = $desc;
        $category->is_active = true;
        $category->save();
    }

    private function makeReport(int $categoryId, string $subject, ?int $riskLevelId): Report
    {
        $owner = User::factory()->create();
        Auth::login($owner);

        $report = new Report();
        $report->subject = $subject;
        $report->remark = 'x';
        $report->report_category_id = $categoryId;
        $report->report_status_id = 2;
        $report->risk_level_id = $riskLevelId;
        $report->submitted_at = now();
        $report->save();

        Auth::logout();

        return $report->fresh();
    }

    public function test_command_classifies_unclassified_aduan_only()
    {
        Notification::fake();
        $this->ensureCategory(3, 'Komputer Meja (Desktop)');

        $level1 = RiskLevel::where('level', 1)->value('id');
        $level5 = RiskLevel::where('level', 5)->value('id');

        $unclassified = $this->makeReport(3, 'Komputer ada virus', null);
        $officerSet = $this->makeReport(3, 'Komputer ada virus', $level5);
        $onDefault = $this->makeReport(3, 'Komputer ada virus', $level1);

        $this->artisan('aduan:classify-risk')->assertSuccessful();

        // Unclassified -> level 4 (virus)
        $this->assertEquals(4, $unclassified->fresh()->riskLevel->level);
        // An officer's level 5 is left alone.
        $this->assertEquals(5, $officerSet->fresh()->riskLevel->level);
        // Default Tahap 1 is untouched without --reclassify.
        $this->assertEquals(1, $onDefault->fresh()->riskLevel->level);
    }

    public function test_reclassify_option_re_evaluates_aduan_on_the_default_level()
    {
        Notification::fake();
        $this->ensureCategory(3, 'Komputer Meja (Desktop)');

        $level1 = RiskLevel::where('level', 1)->value('id');
        $level5 = RiskLevel::where('level', 5)->value('id');

        $onDefault = $this->makeReport(3, 'Komputer ada virus', $level1);
        $officerSet = $this->makeReport(3, 'Printer rosak', $level5);

        $this->artisan('aduan:classify-risk --reclassify')->assertSuccessful();

        $this->assertEquals(4, $onDefault->fresh()->riskLevel->level);
        // Still never overrides a deliberate level above the default.
        $this->assertEquals(5, $officerSet->fresh()->riskLevel->level);
    }

    public function test_dry_run_writes_nothing()
    {
        Notification::fake();
        $this->ensureCategory(3, 'Komputer Meja (Desktop)');

        $report = $this->makeReport(3, 'Komputer ada virus', null);

        $this->artisan('aduan:classify-risk --dry-run')->assertSuccessful();

        $this->assertNull($report->fresh()->risk_level_id);
    }

    public function test_command_writes_no_trail_rows()
    {
        Notification::fake();
        $this->ensureCategory(3, 'Komputer Meja (Desktop)');

        $report = $this->makeReport(3, 'Komputer ada virus', null);
        $trailsBefore = $report->trails()->count();

        $this->artisan('aduan:classify-risk')->assertSuccessful();

        $this->assertEquals($trailsBefore, $report->fresh()->trails()->count());
    }
}
