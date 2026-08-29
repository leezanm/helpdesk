<?php

namespace Tests\Feature\Livewire\Laporan;

use App\Livewire\Laporan\ReportAll;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class ReportAllTest extends FeatureTestCase
{
    private function makeReport(User $owner, int $status, ?string $bahagian = null): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login($owner);
        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->submitted_at = now();
        $report->bahagian_pengadu = $bahagian;
        $report->save();
        if ($status !== 2) {
            $report->report_status_id = $status;
            $report->save();
        }
        Auth::logout();

        return $report->fresh();
    }

    public function test_lists_reports_in_default_range()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $r = $this->makeReport(User::factory()->create(), 2);

        Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->assertOk()
            ->assertSee($r->ticket_no)
            ->assertSee('Penapis Laporan');
    }

    public function test_filter_by_status()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $sent = $this->makeReport(User::factory()->create(), 2);
        $done = $this->makeReport(User::factory()->create(), 5);

        Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->set('status', '5')
            ->assertSee($done->ticket_no)
            ->assertDontSee($sent->ticket_no);
    }

    public function test_filter_by_bahagian()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $a = $this->makeReport(User::factory()->create(), 2, 'Bahagian Akaun');
        $b = $this->makeReport(User::factory()->create(), 2, 'Bahagian Kewangan');

        Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->set('bahagian', 'Bahagian Akaun')
            ->assertSee($a->ticket_no)
            ->assertDontSee($b->ticket_no);
    }

    public function test_filter_by_pegawai()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);

        $assigned = $this->makeReport(User::factory()->create(), 4);
        $trail = new ReportTrail();
        $trail->report_id = $assigned->id;
        $trail->message = 'assigned';
        $trail->updated_by_id = $admin->id;
        $trail->assigned_to_id = $officer->id;
        $trail->status_id = 4;
        $trail->save();

        $other = $this->makeReport(User::factory()->create(), 4);

        Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->set('pegawai', (string) $officer->id)
            ->assertSee($assigned->ticket_no)
            ->assertDontSee($other->ticket_no);
    }

    public function test_date_range_excludes_outside()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $r = $this->makeReport(User::factory()->create(), 2);

        Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->set('dateFrom', now()->subMonths(3)->format('Y-m-d'))
            ->set('dateTo', now()->subMonths(2)->format('Y-m-d'))
            ->assertDontSee($r->ticket_no);
    }
}
