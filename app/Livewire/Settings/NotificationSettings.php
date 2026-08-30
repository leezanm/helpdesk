<?php

namespace App\Livewire\Settings;

use App\Models\NotificationSetting;
use App\Support\NotificationEvents;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Superadmin screen to configure, per aduan event, whether an email is sent
 * and who receives it (roles + reporter + assigned officer + extra emails).
 */
class NotificationSettings extends Component
{
    /** @var array<int,array<string,mixed>> editable copy of the settings rows */
    public array $rows = [];

    public function mount(): void
    {
        // Ensure a row exists for every known event (self-heal if seeder not run).
        foreach (NotificationEvents::defaults() as $key => $cfg) {
            NotificationSetting::firstOrCreate(['event_key' => $key], [
                'label' => $cfg['label'],
                'enabled' => $cfg['enabled'],
                'roles' => $cfg['roles'],
                'include_reporter' => $cfg['include_reporter'],
                'include_assigned_officer' => $cfg['include_assigned_officer'],
                'extra_emails' => $cfg['extra_emails'],
            ]);
        }

        $this->rows = NotificationSetting::orderBy('id')->get()->map(fn ($s) => [
            'id' => $s->id,
            'event_key' => $s->event_key,
            'label' => $s->label,
            'enabled' => (bool) $s->enabled,
            'roles' => $s->roles ?: [],
            'include_reporter' => (bool) $s->include_reporter,
            'include_assigned_officer' => (bool) $s->include_assigned_officer,
            'extra_emails_text' => implode(', ', $s->extra_emails ?: []),
        ])->all();
    }

    public function save(): void
    {
        $this->authorize('manage.role'); // superadmin-only area (see route gate)

        foreach ($this->rows as $row) {
            $emails = collect(preg_split('/[,;\s]+/', (string) $row['extra_emails_text']))
                ->map(fn ($e) => trim($e))
                ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                ->unique()->values()->all();

            NotificationSetting::where('id', $row['id'])->update([
                'enabled' => (bool) $row['enabled'],
                'roles' => array_values($row['roles'] ?: []),
                'include_reporter' => (bool) $row['include_reporter'],
                'include_assigned_officer' => (bool) $row['include_assigned_officer'],
                'extra_emails' => $emails,
            ]);
        }

        session()->flash('settings-message', 'Tetapan notifikasi berjaya disimpan.');
    }

    public function render()
    {
        return view('livewire.settings.notification-settings', [
            'allRoles' => Role::orderBy('desc')->get(['name', 'desc']),
        ]);
    }
}
