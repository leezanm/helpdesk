<?php

namespace Database\Seeders;

use App\Models\NotificationSetting;
use App\Support\NotificationEvents;
use Illuminate\Database\Seeder;

/**
 * Seed the default per-event notification recipients. Uses firstOrCreate so it
 * never overwrites settings a superadmin has already customised, and re-running
 * is safe. Defaults mirror the previous hardcoded behaviour.
 */
class NotificationSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (NotificationEvents::defaults() as $eventKey => $cfg) {
            NotificationSetting::firstOrCreate(
                ['event_key' => $eventKey],
                [
                    'label' => $cfg['label'],
                    'enabled' => $cfg['enabled'],
                    'roles' => $cfg['roles'],
                    'include_reporter' => $cfg['include_reporter'],
                    'include_assigned_officer' => $cfg['include_assigned_officer'],
                    'extra_emails' => $cfg['extra_emails'],
                ]
            );
        }
    }
}
