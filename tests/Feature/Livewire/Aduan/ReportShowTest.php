<?php

namespace Tests\Feature\Livewire\Aduan;

use App\Livewire\Aduan\ReportShow;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use App\Notifications\ReportAssignedNotification;
use App\Notifications\ReportDoneAdminNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class ReportShowTest extends FeatureTestCase
{
    /**
     * Create a report at the given status. Created as "Baru" (2) first so the
     * observer stamps ticket + first trail, then advanced. If $officer given,
     * an assignment trail is added so it becomes the current assigned officer.
     */
    private function makeReport(User $owner, int $status = 2, ?User $officer = null): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login($owner);

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
            $trail = new ReportTrail();
            $trail->message = 'setup';
            $trail->updated_by_id = $owner->id;
            $trail->status_id = 3;
            if ($officer) {
                $trail->assigned_to_id = $officer->id;
            }
            $report->trails()->save($trail);
        }

        Auth::logout();

        return $report->fresh();
    }

    public function test_owner_can_view_own_report()
    {
        Notification::fake();
        $owner = User::factory()->create();
        $report = $this->makeReport($owner, 3);

        Livewire::actingAs($owner)->test(ReportShow::class, ['report' => $report])->assertOk();
    }

    public function test_stranger_cannot_view_report()
    {
        Notification::fake();
        $report = $this->makeReport(User::factory()->create(), 3);
        $stranger = User::factory()->create(); // unrelated, no assignment, no admin

        Livewire::actingAs($stranger)->test(ReportShow::class, ['report' => $report])->assertForbidden();
    }

    public function test_wakil_can_view_report_filed_on_behalf()
    {
        Notification::fake();
        $owner = User::factory()->create();
        $report = $this->makeReport($owner, 3);
        $wakil = User::factory()->create();
        $report->bp_user_id = $wakil->id;
        $report->save();

        Livewire::actingAs($wakil)->test(ReportShow::class, ['report' => $report])->assertOk();
    }

    public function test_assigned_officer_can_view_report()
    {
        Notification::fake();
        $owner = User::factory()->create();
        $officer = User::factory()->create();
        $report = $this->makeReport($owner, 4, $officer); // assignment trail to officer

        Livewire::actingAs($officer)->test(ReportShow::class, ['report' => $report])->assertOk();
    }

    public function test_helpdesk_viewing_new_report_auto_marks_reviewed()
    {
        Notification::fake();
        $report = $this->makeReport(User::factory()->create(), 2); // Baru
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        Livewire::actingAs($admin)->test(ReportShow::class, ['report' => $report])->assertOk();

        // Baru (2) -> Aduan Disemak (3)
        $this->assertEquals(3, $report->fresh()->report_status_id);
        $this->assertDatabaseHas('report_trails', ['report_id' => $report->id, 'status_id' => 3]);
    }

    public function test_helpdesk_forwards_to_officer()
    {
        Notification::fake();
        $report = $this->makeReport(User::factory()->create(), 3);
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);

        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['report' => $report])
            ->set('tindakan', '1')
            ->set('peranan', 'jtk')
            ->set('assigned_to_id', $officer->id)
            ->call('saveAdminTask')
            ->assertHasNoErrors();

        $this->assertEquals(4, $report->fresh()->report_status_id); // Dalam Tindakan
    }

    public function test_penyelia_is_notified_on_assignment_and_can_close()
    {
        Notification::fake();
        $report = $this->makeReport(User::factory()->create(), 3);
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);
        $penyelia = User::factory()->create();
        $penyelia->syncRoles(['penyelia']);

        // Assign -> penyelia (supervisor) is notified alongside the officer.
        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['report' => $report])
            ->set('tindakan', '1')
            ->set('peranan', 'jtk')
            ->set('assigned_to_id', $officer->id)
            ->call('saveAdminTask')
            ->assertHasNoErrors();

        Notification::assertSentTo($penyelia, ReportAssignedNotification::class);
        Notification::assertSentTo($officer, ReportAssignedNotification::class);

        // Penyelia can view any report and close from Selesai Tindakan (7).
        $report7 = $this->makeReport(User::factory()->create(), 7);
        Livewire::actingAs($penyelia)
            ->test(ReportShow::class, ['report' => $report7])
            ->assertOk()
            ->set('rating', 5)
            ->call('closeReport')
            ->assertHasNoErrors();

        $this->assertEquals(5, $report7->fresh()->report_status_id); // Selesai
    }

    public function test_officer_completes_action_with_lesson_learn()
    {
        Notification::fake();
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);
        $report = $this->makeReport(User::factory()->create(), 4, $officer); // Dalam Tindakan, assigned

        Livewire::actingAs($officer)
            ->test(ReportShow::class, ['report' => $report])
            ->set('outcome', 'selesai')
            ->set('punca_masalah', 'Kabel rangkaian longgar')
            ->set('langkah_tindakan', 'Kabel diganti dan diuji semula')
            ->call('saveOfficerAction')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertEquals(7, $report->report_status_id); // Selesai Tindakan
        $this->assertDatabaseHas('report_trails', [
            'report_id' => $report->id,
            'status_id' => 9,
            'punca_masalah' => 'Kabel rangkaian longgar',
            'langkah_tindakan' => 'Kabel diganti dan diuji semula',
        ]);
    }

    public function test_officer_completion_requires_punca_and_langkah()
    {
        Notification::fake();
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);
        $report = $this->makeReport(User::factory()->create(), 4, $officer);

        Livewire::actingAs($officer)
            ->test(ReportShow::class, ['report' => $report])
            ->set('outcome', 'selesai')
            ->call('saveOfficerAction')
            ->assertHasErrors(['punca_masalah', 'langkah_tindakan']);
    }

    public function test_officer_escalates_needs_further_action()
    {
        Notification::fake();
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);
        $report = $this->makeReport(User::factory()->create(), 4, $officer);

        Livewire::actingAs($officer)
            ->test(ReportShow::class, ['report' => $report])
            ->set('outcome', 'lanjut')
            ->set('punca_masalah', 'Kerosakan perkakasan pelayan')
            ->set('kenapa_lanjut', 'Perlu ganti pelayan, di luar bidang kuasa')
            ->call('saveOfficerAction')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertEquals(8, $report->report_status_id); // Tindakan Lanjut Diperlukan
        $this->assertDatabaseHas('report_trails', [
            'report_id' => $report->id,
            'status_id' => 10,
            'kenapa_lanjut' => 'Perlu ganti pelayan, di luar bidang kuasa',
        ]);
    }

    public function test_close_from_selesai_tindakan_sets_selesai_with_default_rating()
    {
        Notification::fake();
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter, 7); // Selesai Tindakan

        Livewire::actingAs($reporter)
            ->test(ReportShow::class, ['report' => $report])
            ->assertSet('rating', 5) // default 5 stars
            ->set('feedback', 'Terima kasih')
            ->call('closeReport')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertEquals(5, $report->report_status_id); // Selesai
        $this->assertEquals(5, $report->rating);
        $this->assertNotNull($report->completed_at);
    }

    public function test_helpdesk_can_kiv_from_needs_further_action()
    {
        Notification::fake();
        $report = $this->makeReport(User::factory()->create(), 8); // Tindakan Lanjut
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['report' => $report])
            ->set('tindakan', '3')
            ->set('kiv_start_at', now()->format('Y-m-d'))
            ->set('kiv_reason', 'Menunggu bekalan')
            ->call('saveAdminTask')
            ->assertHasNoErrors();

        $this->assertEquals(6, $report->fresh()->report_status_id); // KIV
    }

    public function test_admin_can_change_category()
    {
        Notification::fake();
        $report = $this->makeReport(User::factory()->create(), 4);
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
