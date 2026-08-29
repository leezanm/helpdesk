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

    public function test_bagi_pihak_records_report_under_represented_person_with_their_bahagian()
    {
        Notification::fake();
        // Helpdesk officer (can represent any bahagian).
        $officer = User::factory()->create(['ldap_bahagian' => 'Bahagian A']);
        $officer->givePermissionTo('aduan.adminTask');
        // Represented person from a DIFFERENT bahagian.
        $represented = User::factory()->create(['ldap_bahagian' => 'Bahagian B']);
        $category = ReportCategory::create(['desc' => 'Rangkaian', 'is_active' => true]);

        Livewire::actingAs($officer)
            ->test(ReportForm::class)
            ->set('is_bp', true)
            ->set('bp_username', $represented->username)
            ->call('lookupBpUser')
            ->assertSet('bpLookupError', null)
            ->assertSet('bp_user_id', $represented->id)
            ->set('subject', 'Aduan bagi pihak')
            ->set('remark', 'kerosakan di Bahagian B')
            ->set('report_category_id', $category->id)
            ->call('save', 2)
            ->assertHasNoErrors();

        $report = Report::firstWhere('subject', 'Aduan bagi pihak');
        $this->assertNotNull($report);
        // Recorded UNDER the represented person, with THEIR bahagian.
        $this->assertEquals($represented->id, $report->report_by_id);
        $this->assertEquals('Bahagian B', $report->bahagian_pengadu);
        // Acting officer kept as the wakil, so they retain visibility.
        $this->assertEquals($officer->id, $report->bp_user_id);
        $this->assertTrue((bool) $report->is_bp);
    }

    public function test_normal_user_cannot_represent_someone_from_another_bahagian()
    {
        // Normal user without aduan.adminTask.
        $user = User::factory()->create(['ldap_bahagian' => 'Bahagian A']);
        $other = User::factory()->create(['ldap_bahagian' => 'Bahagian B']);

        Livewire::actingAs($user)
            ->test(ReportForm::class)
            ->set('is_bp', true)
            ->set('bp_username', $other->username)
            ->call('lookupBpUser')
            ->assertSet('bp_user_id', null)
            ->assertSet('bpLookupError', 'Anda hanya boleh mewakili pengguna dari bahagian anda sahaja.');
    }

    public function test_normal_user_can_represent_someone_from_own_bahagian()
    {
        $user = User::factory()->create(['ldap_bahagian' => 'Bahagian A']);
        $peer = User::factory()->create(['ldap_bahagian' => 'Bahagian A']);

        Livewire::actingAs($user)
            ->test(ReportForm::class)
            ->set('is_bp', true)
            ->set('bp_username', $peer->username)
            ->call('lookupBpUser')
            ->assertSet('bpLookupError', null)
            ->assertSet('bp_user_id', $peer->id);
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
