<?php

namespace Tests\Feature\Livewire\Aduan;

use App\Livewire\Aduan\ReportForm;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\FeatureTestCase;

class ReportFormTest extends FeatureTestCase
{
    public function test_create_validates_required_fields()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportForm::class)
            ->call('save', 2)
            ->assertHasErrors(['subject', 'report_category_id', 'remark']);
    }

    public function test_creates_and_submits_report_with_trail_and_ticket()
    {
        Notification::fake();
        $user = User::factory()->create();
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ReportForm::class)
            ->set('subject', 'Internet mati')
            ->set('remark', 'Tiada sambungan')
            ->set('report_category_id', $category->id)
            ->call('save', 2)
            ->assertHasNoErrors()
            ->assertRedirect();

        $report = Report::first();
        $this->assertNotNull($report);
        $this->assertEquals(2, $report->report_status_id);
        $this->assertNotNull($report->ticket_no);          // observer generated it
        $this->assertNotNull($report->submitted_at);
        // observer created the "Aduan dihantar" trail (status 2)
        $this->assertDatabaseHas('report_trails', ['report_id' => $report->id, 'status_id' => 2]);
    }

    public function test_creates_report_with_attachment()
    {
        Notification::fake();
        Storage::fake('local');
        $user = User::factory()->create();
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ReportForm::class)
            ->set('subject', 'Ada lampiran')
            ->set('remark', 'lihat fail')
            ->set('report_category_id', $category->id)
            ->set('attachments', [UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf')])
            ->call('save', 2)
            ->assertHasNoErrors();

        $report = Report::first();
        $this->assertCount(1, $report->attachments);
        $this->assertStringStartsWith('storage/aduan/attachments', $report->attachments->first()->path);
    }

    public function test_bp_fields_required_when_is_bp()
    {
        $user = User::factory()->create();
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ReportForm::class)
            ->set('is_bp', true)
            ->set('subject', 'x')
            ->set('remark', 'y')
            ->set('report_category_id', $category->id)
            ->call('save', 2)
            ->assertHasErrors(['bp_username', 'bp_name', 'bp_email', 'bp_bahagian']);
    }

    public function test_draft_does_not_set_submitted_at()
    {
        $user = User::factory()->create();
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ReportForm::class)
            ->set('subject', 'Draf sahaja')
            ->set('remark', 'belum hantar')
            ->set('report_category_id', $category->id)
            ->call('save', 1)
            ->assertHasNoErrors();

        $report = Report::first();
        $this->assertEquals(1, $report->report_status_id);
        $this->assertNull($report->submitted_at);
    }
}
