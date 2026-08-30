<?php

namespace Tests\Feature;

use App\Models\NotificationSetting;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\NotificationEvents;
use App\Support\NotificationRecipients;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\FeatureTestCase;

class NotificationRecipientsTest extends FeatureTestCase
{
    private function seedSetting(string $key, array $attrs): NotificationSetting
    {
        return NotificationSetting::updateOrCreate(
            ['event_key' => $key],
            array_merge([
                'label' => $key,
                'enabled' => true,
                'roles' => [],
                'include_reporter' => false,
                'include_assigned_officer' => false,
                'extra_emails' => [],
            ], $attrs)
        );
    }

    private function makeReport(User $owner): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        \Illuminate\Support\Facades\Auth::login($owner); // observer stamps report_by_id + first trail
        $r = new Report();
        $r->subject = 'x';
        $r->remark = 'y';
        $r->report_category_id = $category->id;
        $r->report_status_id = 2;
        $r->report_by_id = $owner->id;
        $r->submitted_at = now();
        $r->save();
        \Illuminate\Support\Facades\Auth::logout();

        return $r->fresh();
    }

    public function test_resolves_by_role()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $this->seedSetting(NotificationEvents::ADUAN_DITERIMA_ADMIN, ['roles' => ['helpdesk.admin']]);

        $recipients = NotificationRecipients::for(NotificationEvents::ADUAN_DITERIMA_ADMIN, null);

        $this->assertTrue($recipients->contains(fn ($u) => $u->id === $admin->id));
    }

    public function test_disabled_event_returns_no_recipients()
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);
        $this->seedSetting(NotificationEvents::ADUAN_DITERIMA_ADMIN, ['roles' => ['helpdesk.admin'], 'enabled' => false]);

        $this->assertCount(0, NotificationRecipients::for(NotificationEvents::ADUAN_DITERIMA_ADMIN, null));
    }

    public function test_includes_reporter_and_extra_emails()
    {
        $reporter = User::factory()->create();
        $report = $this->makeReport($reporter);
        $this->seedSetting(NotificationEvents::ADUAN_SELESAI_PENGADU, [
            'include_reporter' => true,
            'extra_emails' => ['unit@example.test', 'not-an-email'],
        ]);

        $recipients = NotificationRecipients::for(NotificationEvents::ADUAN_SELESAI_PENGADU, $report);

        $this->assertTrue($recipients->contains(fn ($u) => $u instanceof User && $u->id === $reporter->id));
        // one valid extra email becomes an AnonymousNotifiable; invalid dropped
        $this->assertEquals(1, $recipients->filter(fn ($u) => $u instanceof AnonymousNotifiable)->count());
    }

    public function test_includes_assigned_officer_from_latest_trail()
    {
        $reporter = User::factory()->create();
        $officer = User::factory()->create();
        $report = $this->makeReport($reporter);
        $trail = new \App\Models\ReportTrail();
        $trail->message = 'assigned';
        $trail->updated_by_id = $reporter->id;
        $trail->assigned_to_id = $officer->id;
        $trail->status_id = 4;
        $report->trails()->save($trail);
        $this->seedSetting(NotificationEvents::ADUAN_DITUGASKAN, ['include_assigned_officer' => true]);

        $recipients = NotificationRecipients::for(NotificationEvents::ADUAN_DITUGASKAN, $report);

        $this->assertTrue($recipients->contains(fn ($u) => $u instanceof User && $u->id === $officer->id));
    }
}
