<?php

namespace Tests\Feature;

use App\Models\NotificationSetting;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\User;
use App\Support\NotificationEvents;
use App\Support\NotificationRecipients;
use App\Livewire\Aduan\ReportShow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * A notification setting may name a role that has since been deleted (the
 * "penyelia" case). Resolving recipients must treat it as "nobody", never
 * throw — otherwise the aduan action that triggers the email returns a 500.
 */
class NotificationStaleRoleTest extends FeatureTestCase
{
    public function test_unknown_role_in_settings_resolves_to_no_recipients()
    {
        NotificationSetting::where('event_key', NotificationEvents::ADUAN_DITUGASKAN)->delete();

        NotificationSetting::create([
            'event_key' => NotificationEvents::ADUAN_DITUGASKAN,
            'label' => 'Aduan Ditugaskan kepada Pegawai',
            'enabled' => true,
            'roles' => ['penyelia'],          // deleted role
            'include_reporter' => false,
            'include_assigned_officer' => false,
            'extra_emails' => [],
        ]);

        $recipients = NotificationRecipients::for(NotificationEvents::ADUAN_DITUGASKAN);

        $this->assertCount(0, $recipients);
    }

    public function test_known_roles_still_resolve_when_mixed_with_an_unknown_one()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        NotificationSetting::where('event_key', NotificationEvents::ADUAN_SELESAI_TINDAKAN)->delete();

        NotificationSetting::create([
            'event_key' => NotificationEvents::ADUAN_SELESAI_TINDAKAN,
            'label' => 'Pegawai Selesai Tindakan',
            'enabled' => true,
            'roles' => ['helpdesk.admin', 'penyelia'],
            'include_reporter' => false,
            'include_assigned_officer' => false,
            'extra_emails' => [],
        ]);

        $recipients = NotificationRecipients::for(NotificationEvents::ADUAN_SELESAI_TINDAKAN);

        $this->assertCount(1, $recipients);
        $this->assertEquals($admin->id, $recipients->first()->id);
    }

    public function test_assigning_an_officer_succeeds_with_a_stale_role_configured()
    {
        Notification::fake();

        NotificationSetting::where('event_key', NotificationEvents::ADUAN_DITUGASKAN)->delete();
        NotificationSetting::create([
            'event_key' => NotificationEvents::ADUAN_DITUGASKAN,
            'label' => 'Aduan Ditugaskan kepada Pegawai',
            'enabled' => true,
            'roles' => ['penyelia'],
            'include_reporter' => false,
            'include_assigned_officer' => true,
            'extra_emails' => [],
        ]);

        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        $owner = User::factory()->create();
        Auth::login($owner);

        $report = new Report();
        $report->subject = 'Ujian';
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->risk_level_id = \App\Models\Ref\RiskLevel::where('level', 1)->value('id');
        $report->submitted_at = now();
        $report->save();
        $report->report_status_id = 3;
        $report->save();

        $trail = new ReportTrail();
        $trail->message = 'setup';
        $trail->updated_by_id = $owner->id;
        $trail->status_id = 3;
        $report->trails()->save($trail);
        Auth::logout();

        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $officer = User::factory()->create();
        $officer->syncRoles(['jtk']);

        // This is the exact action that returned a 500 on staging.
        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['report' => $report->fresh()])
            ->set('tindakan', '1')
            ->set('peranan', 'jtk')
            ->set('assigned_to_id', $officer->id)
            ->call('saveAdminTask')
            ->assertHasNoErrors();

        $this->assertEquals(4, $report->fresh()->report_status_id);
    }

    public function test_migration_strips_the_removed_role_from_stored_settings()
    {
        $setting = NotificationSetting::create([
            'event_key' => 'ujian_peranan_lapuk',
            'label' => 'Ujian',
            'enabled' => true,
            'roles' => ['helpdesk.admin', 'penyelia'],
            'include_reporter' => false,
            'include_assigned_officer' => false,
            'extra_emails' => [],
        ]);

        require_once database_path('migrations/2026_09_02_000000_remove_penyelia_from_notification_settings.php');
        (require database_path('migrations/2026_09_02_000000_remove_penyelia_from_notification_settings.php'))->up();

        $this->assertEquals(['helpdesk.admin'], $setting->fresh()->roles);
    }
}
