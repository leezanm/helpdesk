<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Superadmin-configurable recipients for a single aduan notification event.
 *
 * event_key values (see NotificationEvents):
 *   aduan_diterima_admin   — new aduan submitted (to helpdesk/monitors)
 *   aduan_ditugaskan       — assigned/reassigned to an officer
 *   aduan_selesai_tindakan — officer marked action done / needs further action
 *   aduan_selesai_admin    — aduan closed (Selesai) — admin/monitor copy
 *   aduan_selesai_pengadu  — aduan closed (Selesai) — reporter copy
 *   aduan_dihantar_pengadu — submission acknowledgement to the reporter
 */
class NotificationSetting extends Model
{
    protected $fillable = [
        'event_key', 'label', 'enabled', 'roles',
        'include_reporter', 'include_assigned_officer', 'extra_emails',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'include_reporter' => 'boolean',
        'include_assigned_officer' => 'boolean',
        'roles' => 'array',
        'extra_emails' => 'array',
    ];
}
