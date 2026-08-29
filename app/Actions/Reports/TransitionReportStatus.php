<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\ReportTrail;
use App\Models\User;
use App\Notifications\ReportAssignedNotification;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

/**
 * Admin task transition on a report (aduan). Extracted from
 * ReportController::adminTask. Handles the four "tindakan":
 *   1 = Majukan Kepada (assign to a technical officer)  -> status 4, trail 4
 *   2 = Penugasan Semula (reassign)                     -> status 4, trail 6
 *   3 = KIV                                             -> status 6, trail 7
 *   4 = Tamat KIV (end KIV)                             -> status 4, trail 8
 *
 * @param array{tindakan:int,assigned_to_id?:int,peranan?:string,reassignment_remark?:string,kiv_start_at?:string,kiv_reason?:string,kiv_end_at?:string} $data
 */
class TransitionReportStatus
{
    use NotifiesReport;

    public function handle(Report $report, array $data): Report
    {
        $tindakan = (int) $data['tindakan'];
        $user = isset($data['assigned_to_id']) ? User::find($data['assigned_to_id']) : null;
        $role = isset($data['peranan']) ? Role::where('name', $data['peranan'])->first() : null;

        $trail = $report->currentTrail->replicate(['technical_remark', 'reassignment_remark']);

        switch ($tindakan) {
            case 1:
                $report->report_status_id = 4;
                $trailStatusId = 4;
                $trail->assigned_to_id = $data['assigned_to_id'];
                $trail->assigned_to_role_id = $role?->id;
                $message = 'Aduan telah dihantar kepada <a class="userProfile" data-userId="'.$user->id.'" role="button">'.$user->name.' ('.$role->desc.')</a> untuk disemak';
                break;
            case 2:
                $report->report_status_id = 4;
                $trailStatusId = 6;
                $trail->assigned_to_id = $data['assigned_to_id'];
                $trail->assigned_to_role_id = $role?->id;
                $message = 'Aduan telah ditugaskan semula kepada <a class="userProfile" data-userId="'.$user->id.'" role="button">'.$user->name.' ('.$role->desc.')</a> untuk disemak
                            <br> <b>Sebab Penugasan</b> <br>
                            '.($data['reassignment_remark'] ?? '');
                break;
            case 3:
                $report->report_status_id = 6;
                $report->kiv_start_at = $data['kiv_start_at'];
                $report->kiv_reason = $data['kiv_reason'] ?? null;
                $trailStatusId = 7;
                $message = 'Aduan telah di KIV bermula pada <b>'.convertDateFormat($data['kiv_start_at'], 'LL').'</b>';
                break;
            case 4:
                $report->report_status_id = 4;
                $report->kiv_end_at = $data['kiv_end_at'];
                $trailStatusId = 8;
                $message = 'KIV berakhir pada <b>'.convertDateFormat($data['kiv_end_at'], 'LL').'</b>';
                break;
            default:
                return $report;
        }

        $trail->message = $message;
        $trail->updated_by_id = Auth::id();
        $trail->reassignment_remark = $data['reassignment_remark'] ?? null;
        $trail->status_id = $trailStatusId;

        $report->save();
        $report->trails()->save($trail);

        if ($user) {
            $this->notifySingle($user, new ReportAssignedNotification($report));
        }

        return $report;
    }
}
