<?php

namespace Tests\Feature\Livewire\Aduan;

use App\Livewire\Aduan\LessonLearn;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class LessonLearnTest extends FeatureTestCase
{
    /** Create a resolved-action trail (status 9) carrying lesson-learn data. */
    private function makeLesson(string $category, string $punca, string $langkah): ReportTrail
    {
        $cat = ReportCategory::firstOrCreate(['desc' => $category], ['is_active' => true]);
        $owner = User::factory()->create();
        Auth::login($owner);

        $report = new Report();
        $report->subject = 'Aduan '.$punca;
        $report->remark = 'x';
        $report->report_category_id = $cat->id;
        $report->report_status_id = 2;
        $report->submitted_at = now();
        $report->save();
        $report->report_status_id = 7;
        $report->save();

        $trail = new ReportTrail();
        $trail->report_id = $report->id;
        $trail->message = 'selesai tindakan';
        $trail->updated_by_id = $owner->id;
        $trail->status_id = 9;
        $trail->punca_masalah = $punca;
        $trail->langkah_tindakan = $langkah;
        $trail->save();

        Auth::logout();

        return $trail;
    }

    public function test_lists_resolved_lesson_entries()
    {
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);
        $this->makeLesson('Rangkaian', 'Kabel longgar', 'Ganti kabel');

        Livewire::actingAs($officer)
            ->test(LessonLearn::class)
            ->assertOk()
            ->assertSee('Kabel longgar')
            ->assertSee('Ganti kabel');
    }

    public function test_search_filters_entries()
    {
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);
        $this->makeLesson('Rangkaian', 'Kabel longgar', 'Ganti kabel');
        $this->makeLesson('Perkakasan', 'Cakera keras rosak', 'Ganti SSD');

        Livewire::actingAs($officer)
            ->test(LessonLearn::class)
            ->set('search', 'Kabel')
            ->assertSee('Kabel longgar')
            ->assertDontSee('Cakera keras rosak');
    }

    public function test_only_resolved_trails_appear()
    {
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);

        // A trail that is NOT status 9 (e.g. escalation, status 10) should not show.
        $cat = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        $owner = User::factory()->create();
        Auth::login($owner);
        $report = new Report();
        $report->subject = 'Escalated'; $report->remark = 'x';
        $report->report_category_id = $cat->id; $report->report_status_id = 2; $report->submitted_at = now();
        $report->save();
        $report->report_status_id = 8; $report->save();
        $t = new ReportTrail();
        $t->report_id = $report->id; $t->message = 'lanjut'; $t->updated_by_id = $owner->id;
        $t->status_id = 10; $t->punca_masalah = 'Punca escalated'; $t->kenapa_lanjut = 'x';
        $t->save();
        Auth::logout();

        Livewire::actingAs($officer)
            ->test(LessonLearn::class)
            ->assertDontSee('Punca escalated');
    }
}
