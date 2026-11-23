<?php

namespace Tests\Feature\Aduan;

use App\Console\Commands\SeedDemoLessonLearn;
use App\Livewire\Aduan\LessonLearn;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class SeedDemoLessonLearnTest extends FeatureTestCase
{
    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officer = User::factory()->create(['username' => 'jtk.demo']);
        $this->officer->syncRoles(['jtk']);
    }

    private function category(int $id, string $desc): ReportCategory
    {
        if ($existing = ReportCategory::find($id)) {
            return $existing;
        }

        $category = new ReportCategory();
        $category->id = $id;
        $category->desc = $desc;
        $category->is_active = true;
        $category->save();

        return $category;
    }

    /** A completed aduan with an assignment trail but no resolution record. */
    private function completedReport(int $categoryId = 4, int $status = 5): Report
    {
        $this->category($categoryId, 'Pencetak');
        $owner = User::factory()->create();
        Auth::login($owner);

        $report = new Report();
        $report->subject = 'Pencetak tidak berfungsi';
        $report->remark = 'x';
        $report->report_category_id = $categoryId;
        $report->report_status_id = 2;
        $report->submitted_at = now()->subDays(10);
        $report->save();

        $report->report_status_id = $status;
        $report->completed_at = now()->subDays(2);
        $report->save();

        $trail = new ReportTrail();
        $trail->message = 'ditugaskan';
        $trail->status_id = 4;
        $trail->updated_by_id = $owner->id;
        $trail->assigned_to_id = $this->officer->id;
        $report->trails()->save($trail);

        Auth::logout();

        return $report->fresh();
    }

    public function test_creates_a_resolution_record_for_completed_aduan()
    {
        $report = $this->completedReport();

        $this->artisan('demo:seed-lesson-learn --force')->assertSuccessful();

        $trail = $report->trails()->where('status_id', 9)->first();

        $this->assertNotNull($trail);
        $this->assertNotEmpty($trail->punca_masalah);
        $this->assertNotEmpty($trail->langkah_tindakan);
        // Credited to the officer who actually handled the aduan.
        $this->assertEquals($this->officer->id, $trail->updated_by_id);
    }

    public function test_records_are_marked_as_demo_data()
    {
        $report = $this->completedReport();

        $this->artisan('demo:seed-lesson-learn --force')->assertSuccessful();

        $trail = $report->trails()->where('status_id', 9)->first();

        $this->assertStringContainsString(SeedDemoLessonLearn::DEMO_MARKER, $trail->catatan_teknikal);
        $this->assertStringContainsString(SeedDemoLessonLearn::DEMO_MARKER, $trail->message);
    }

    public function test_resolution_text_follows_the_aduan_category()
    {
        $printer = $this->completedReport(4);

        $this->artisan('demo:seed-lesson-learn --force')->assertSuccessful();

        $trail = $printer->trails()->where('status_id', 9)->first();
        $this->assertStringContainsString('cetakan', $trail->punca_masalah);
    }

    public function test_entries_appear_on_the_lesson_learn_screen()
    {
        $this->completedReport();

        $this->artisan('demo:seed-lesson-learn --force')->assertSuccessful();

        Livewire::actingAs($this->officer)
            ->test(LessonLearn::class)
            ->assertOk()
            ->assertSee('Pencetak tidak berfungsi');
    }

    public function test_resolution_is_dated_before_the_closing_trail()
    {
        $report = $this->completedReport();

        $this->artisan('demo:seed-lesson-learn --force')->assertSuccessful();

        $trail = $report->trails()->where('status_id', 9)->first();

        $this->assertTrue(
            $trail->created_at->lessThanOrEqualTo(\Illuminate\Support\Carbon::parse($report->completed_at)),
            'Rekod lesson learn sepatutnya bertarikh sebelum aduan ditutup.'
        );
    }

    public function test_skips_aduan_that_already_have_a_resolution()
    {
        $report = $this->completedReport();

        $this->artisan('demo:seed-lesson-learn --force')->assertSuccessful();
        $this->artisan('demo:seed-lesson-learn --force')->assertSuccessful();

        $this->assertEquals(1, $report->trails()->where('status_id', 9)->count());
    }

    public function test_ignores_aduan_that_are_not_completed()
    {
        $inAction = $this->completedReport(4, 4);   // Dalam Tindakan

        $this->artisan('demo:seed-lesson-learn --force')->assertSuccessful();

        $this->assertEquals(0, $inAction->trails()->where('status_id', 9)->count());
    }

    public function test_count_option_limits_the_number_documented()
    {
        $this->completedReport();
        $this->completedReport();
        $this->completedReport();

        $this->artisan('demo:seed-lesson-learn --count=2 --force')->assertSuccessful();

        $this->assertEquals(2, ReportTrail::where('status_id', 9)->count());
    }

    public function test_refuses_to_run_in_production()
    {
        $report = $this->completedReport();
        app()['env'] = 'production';

        $this->artisan('demo:seed-lesson-learn --force')->assertFailed();

        $this->assertEquals(0, $report->trails()->where('status_id', 9)->count());
    }
}
