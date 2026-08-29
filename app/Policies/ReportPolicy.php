<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may view / act on an aduan.
 *
 * Note: superadmin is already granted everything via the Gate::before hook in
 * AuthServiceProvider, so it is not repeated here.
 */
class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * View a single aduan. Allowed for:
     *  - helpdesk admin (aduan.adminTask) — sees all aduan
     *  - the pengadu (report_by_id)
     *  - the wakil who filed it on their behalf (bp_user_id)
     *  - any officer who is (or was) assigned to it via a trail
     */
    public function view(User $user, Report $report): bool
    {
        if ($user->can('aduan.adminTask')) {
            return true;
        }

        if ((int) $report->report_by_id === $user->id
            || (int) $report->bp_user_id === $user->id) {
            return true;
        }

        // Officer assigned on any trail of this report.
        return $report->trails()
            ->where('assigned_to_id', $user->id)
            ->exists();
    }

    /**
     * Edit an aduan (only while it is a draft, and only its owner/wakil).
     */
    public function update(User $user, Report $report): bool
    {
        if ($report->report_status_id != 1) {
            return false;
        }

        return (int) $report->report_by_id === $user->id
            || (int) $report->bp_user_id === $user->id;
    }
}
