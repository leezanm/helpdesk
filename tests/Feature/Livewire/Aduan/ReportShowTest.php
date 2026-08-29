<?php

namespace Tests\Feature\Livewire\Aduan;

use App\Livewire\Aduan\ReportShow;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class ReportShowTest extends FeatureTestCase
{
    private function makeReport(User $owner, int $status = 2): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login($owner);

        // Create as Dihantar (2) so the observer generates the first trail
        // (mirrors the real flow), then advance to the requested status with
        // a trail so currentTrail is always present.
        $report = new Report();
        $report->subject = 'Ujian';
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->submitted_at = now();
        $report->save();

        if ($status !== 2) {
            $report->report_status_id = $status;
            $report->save();
            $trail = new \App\Models\ReportTrail();
            $trail->message = 'setup';
            $trail->updated_by_id = $owner->id;
            $trail->status_id = min($status, 6);
            $report->trails()->save($trail);
        }

        Auth::logout();

        return $report->fresh();
    }

    public function test_admin_viewing_dihantar_report_auto_acknowledges()
    {
        Notification::fake();
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, 2);

        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']); // has aduan.adminTask

        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['report' => $report])
            ->assertOk();

        // status 2 -> 3 (AmbilMaklum) with a trail
        $this->assertEquals(3, $report->fresh()->report_status_id);
        $this->assertDatabaseHas('report_trails', ['report_id' => $report->id, 'status_id' => 3]);
    }

    public function test_admin_can_forward_report_to_officer()
    {
        Notification::fake();
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, 3); // AmbilMaklum

        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']); // has aduan.updateTask

        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['report' => $report])
            ->set('tindakan', '1')
            ->set('peranan', 'jtk')
            ->set('assigned_to_id', $officer->id)
            ->call('saveAdminTask')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertEquals(4, $report->report_status_id); // Dalam Tindakan
        $this->assertDatabaseHas('report_trails', ['report_id' => $report->id, 'status_id' => 4, 'assigned_to_id' => $officer->id]);
    }

    public function test_forward_requires_assigned_officer()
    {
        Notification::fake();
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, 3);
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['report' => $report])
            ->set('tindakan', '1')
            ->call('saveAdminTask')
            ->assertHasErrors(['assigned_to_id']);
    }

    public function test_reporter_feedback_marks_selesai()
    {
        Notification::fake();
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, 4); // Dalam Tindakan

        Livewire::actingAs($reporter)
            ->test(ReportShow::class, ['report' => $report])
            ->set('rating', 5)
            ->set('feedback', 'Selesai dengan pantas')
            ->call('saveFeedback')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertEquals(5, $report->report_status_id);
        $this->assertEquals(5, $report->rating);
        $this->assertNotNull($report->completed_at);
    }

    public function test_admin_can_change_category()
    {
        Notification::fake();
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, 4);
        $newCategory = ReportCategory::create(['desc' => 'Perkakasan', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['report' => $report])
            ->set('report_category_id', $newCategory->id)
            ->call('setCategory');

        $this->assertEquals($newCategory->id, $report->fresh()->report_category_id);
    }
}
