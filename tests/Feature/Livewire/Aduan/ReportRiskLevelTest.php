<?php

namespace Tests\Feature\Livewire\Aduan;

use App\Livewire\Aduan\ReportShow;
use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Risk level (tahap risiko) on an aduan: assigned by the helpdesk officer,
 * mandatory before the aduan can be forwarded to a technical officer.
 */
class ReportRiskLevelTest extends FeatureTestCase
{
    /** Aduan at "Aduan Disemak" (3) with NO risk level yet. */
    private function makeUnclassifiedReport(User $owner): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login($owner);

        $report = new Report();
        $report->subject = 'Ujian risiko';
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->submitted_at = now();
        $report->save();

        $report->report_status_id = 3;
        $report->risk_level_id = null;
        $report->save();

        $trail = new ReportTrail();
        $trail->message = 'setup';
        $trail->updated_by_id = $owner->id;
        $trail->status_id = 3;
        $report->trails()->save($trail);

        Auth::logout();

        return $report->fresh();
    }

    private function helpdesk(): User
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        return $admin;
    }

    public function test_migration_seeds_five_risk_levels()
    {
        $this->assertEquals([1, 2, 3, 4, 5], RiskLevel::orderBy('level')->pluck('level')->all());
        $this->assertEquals('Sangat Tinggi', RiskLevel::where('level', 5)->value('desc'));
    }

    public function test_helpdesk_sets_risk_level_and_it_is_logged_in_the_trail()
    {
        Notification::fake();
        $report = $this->makeUnclassifiedReport(User::factory()->create());
        $level = RiskLevel::where('level', 4)->first();

        Livewire::actingAs($this->helpdesk())
            ->test(ReportShow::class, ['report' => $report])
            ->set('risk_level_id', $level->id)
            ->call('setRiskLevel')
            ->assertHasNoErrors();

        $this->assertEquals($level->id, $report->fresh()->risk_level_id);
        // Trail status 11 = "Tahap Risiko Ditetapkan"
        $this->assertDatabaseHas('report_trails', ['report_id' => $report->id, 'status_id' => 11]);
    }

    public function test_risk_level_is_required()
    {
        Notification::fake();
        $report = $this->makeUnclassifiedReport(User::factory()->create());

        Livewire::actingAs($this->helpdesk())
            ->test(ReportShow::class, ['report' => $report])
            ->set('risk_level_id', null)
            ->call('setRiskLevel')
            ->assertHasErrors(['risk_level_id' => 'required']);

        $this->assertNull($report->fresh()->risk_level_id);
    }

    public function test_inactive_risk_level_is_rejected_and_not_offered()
    {
        Notification::fake();
        $report = $this->makeUnclassifiedReport(User::factory()->create());
        $level = RiskLevel::where('level', 2)->first();
        $level->is_active = false;
        $level->save();

        Livewire::actingAs($this->helpdesk())
            ->test(ReportShow::class, ['report' => $report])
            ->assertViewHas('riskLevels', fn ($levels) => $levels->doesntContain('id', $level->id))
            ->set('risk_level_id', $level->id)
            ->call('setRiskLevel')
            ->assertHasErrors(['risk_level_id']);

        $this->assertNull($report->fresh()->risk_level_id);
    }

    public function test_officer_without_helpdesk_permission_cannot_set_risk_level()
    {
        Notification::fake();
        $owner = User::factory()->create();
        $report = $this->makeUnclassifiedReport($owner);

        // The owner may view their aduan but must not classify it.
        Livewire::actingAs($owner)
            ->test(ReportShow::class, ['report' => $report])
            ->set('risk_level_id', RiskLevel::where('level', 1)->value('id'))
            ->call('setRiskLevel')
            ->assertForbidden();

        $this->assertNull($report->fresh()->risk_level_id);
    }

    public function test_cannot_forward_to_officer_before_risk_level_is_set()
    {
        Notification::fake();
        $report = $this->makeUnclassifiedReport(User::factory()->create());
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);

        Livewire::actingAs($this->helpdesk())
            ->test(ReportShow::class, ['report' => $report])
            ->set('tindakan', '1')
            ->set('peranan', 'jtk')
            ->set('assigned_to_id', $officer->id)
            ->call('saveAdminTask')
            ->assertHasErrors(['tindakan']);

        // Still "Aduan Disemak" (3) — not forwarded.
        $this->assertEquals(3, $report->fresh()->report_status_id);
    }

    public function test_selecting_a_level_without_saving_does_not_allow_forwarding()
    {
        Notification::fake();
        $report = $this->makeUnclassifiedReport(User::factory()->create());
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);

        Livewire::actingAs($this->helpdesk())
            ->test(ReportShow::class, ['report' => $report])
            // Picked in the dropdown but setRiskLevel() never called.
            ->set('risk_level_id', RiskLevel::where('level', 3)->value('id'))
            ->set('tindakan', '1')
            ->set('peranan', 'jtk')
            ->set('assigned_to_id', $officer->id)
            ->call('saveAdminTask')
            ->assertHasErrors(['tindakan']);

        $this->assertEquals(3, $report->fresh()->report_status_id);
    }

    public function test_can_forward_once_risk_level_is_saved()
    {
        Notification::fake();
        $report = $this->makeUnclassifiedReport(User::factory()->create());
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);

        Livewire::actingAs($this->helpdesk())
            ->test(ReportShow::class, ['report' => $report])
            ->set('risk_level_id', RiskLevel::where('level', 5)->value('id'))
            ->call('setRiskLevel')
            ->assertHasNoErrors()
            ->set('tindakan', '1')
            ->set('peranan', 'jtk')
            ->set('assigned_to_id', $officer->id)
            ->call('saveAdminTask')
            ->assertHasNoErrors();

        $this->assertEquals(4, $report->fresh()->report_status_id); // Dalam Tindakan
    }
}
