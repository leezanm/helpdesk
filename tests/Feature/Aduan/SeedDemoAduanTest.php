<?php

namespace Tests\Feature\Aduan;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Tests\FeatureTestCase;

class SeedDemoAduanTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Categories the demo aduan refer to (production ids).
        foreach ([3 => 'Komputer Meja (Desktop)', 4 => 'Pencetak', 5 => 'Sidang Video (VC)',
            6 => 'Rangkaian', 7 => 'Emel', 8 => 'Antivirus', 10 => 'Sistem Aplikasi / Laman Web',
            11 => 'Pelayan (Server)', 15 => 'Komputer Riba'] as $id => $desc) {
            if (! ReportCategory::whereKey($id)->exists()) {
                $category = new ReportCategory();
                $category->id = $id;
                $category->desc = $desc;
                $category->is_active = true;
                $category->save();
            }
        }

        // One reporter, one technical officer, one helpdesk officer.
        User::factory()->create(['username' => 'pengadu.demo'])->syncRoles(['pengguna']);
        User::factory()->create(['username' => 'jtk.demo'])->syncRoles(['jtk']);
        User::factory()->create(['username' => 'helpdesk.demo'])->syncRoles(['helpdesk.admin']);
    }

    public function test_creates_ten_aduan_in_the_requested_month()
    {
        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertSuccessful();

        $reports = Report::whereBetween('submitted_at', ['2026-11-01 00:00:00', '2026-11-30 23:59:59'])->get();

        $this->assertCount(10, $reports);
    }

    public function test_every_aduan_has_a_ticket_number_matching_its_own_date()
    {
        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertSuccessful();

        foreach (Report::all() as $report) {
            $this->assertMatchesRegularExpression('#^\d{6}/HQ/\d{3}$#', (string) $report->ticket_no);
            // submitted_at is not cast on the model, so parse it explicitly.
            $this->assertEquals(
                \Illuminate\Support\Carbon::parse($report->submitted_at)->format('ymd'),
                substr($report->ticket_no, 0, 6),
                'Nombor tiket sepatutnya mengikut tarikh aduan.'
            );
        }
    }

    public function test_spreads_aduan_across_the_whole_flow()
    {
        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertSuccessful();

        $statuses = Report::pluck('report_status_id')->unique()->sort()->values()->all();

        // Baru(2), Disemak(3), Dalam Tindakan(4), Selesai(5), KIV(6),
        // Selesai Tindakan(7), Tindakan Lanjut(8)
        $this->assertEquals([2, 3, 4, 5, 6, 7, 8], $statuses);
    }

    public function test_new_aduan_are_left_unclassified_so_the_risk_gate_can_be_demonstrated()
    {
        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertSuccessful();

        $baru = Report::where('report_status_id', 2)->get();

        $this->assertCount(2, $baru);
        $this->assertTrue($baru->every(fn ($r) => $r->risk_level_id === null));

        // Everything past "Baru" carries a risk level.
        $this->assertEquals(0, Report::where('report_status_id', '<>', 2)->whereNull('risk_level_id')->count());
    }

    public function test_aduan_in_action_have_an_assigned_officer_trail()
    {
        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertSuccessful();

        $inAction = Report::where('report_status_id', 4)->first();

        $this->assertNotNull($inAction);
        $this->assertNotNull($inAction->trails()->whereNotNull('assigned_to_id')->first());
    }

    public function test_closed_aduan_carries_rating_and_completion_date()
    {
        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertSuccessful();

        $closed = Report::where('report_status_id', 5)->first();

        $this->assertNotNull($closed);
        $this->assertNotNull($closed->completed_at);
        $this->assertEquals(5, $closed->rating);
    }

    public function test_rerunning_does_not_duplicate()
    {
        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertSuccessful();
        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertSuccessful();

        $this->assertEquals(10, Report::count());
    }

    public function test_count_option_limits_how_many_are_created()
    {
        $this->artisan('demo:seed-aduan --month=2026-11 --count=3 --force')->assertSuccessful();

        $this->assertEquals(3, Report::count());
    }

    public function test_invalid_month_is_rejected()
    {
        $this->artisan('demo:seed-aduan --month=November --force')->assertFailed();

        $this->assertEquals(0, Report::count());
    }

    public function test_refuses_to_run_in_production()
    {
        app()['env'] = 'production';

        $this->artisan('demo:seed-aduan --month=2026-11 --force')->assertFailed();

        $this->assertEquals(0, Report::count());
    }
}
