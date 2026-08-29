<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\ReportTrail;
use App\Models\User;
use App\Notifications\ReportDoneAdminNotification;
use App\Notifications\ReportDoneReporterNotification;
use Illuminate\Support\Facades\Auth;

/**
 * Submit reporter feedback ("soal selidik"), marking the report Selesai
 * (status 5). Extracted from ReportController::updateFeedback.
 */
class SubmitFeedback
{
    use NotifiesReport;

    public function handle(Report $report, int $rating, string $feedback): Report
    {
        $report->report_status_id = 5;
        $report->completed_at = now();
        $report->rating = $rating;
        $report->feedback = $feedback;

        $trail = new ReportTrail();
        $trail->message = 'Aduan telah ditanda selesai dengan <br> <b>Rating</b> <br> '.$rating.' <br> <b>Komen</b> <br> '.$feedback;
        $trail->updated_by_id = Auth::id();
        $trail->status_id = 5;

        $report->save();
        $report->trails()->save($trail);

        $this->notifySingle(Auth::user(), new ReportDoneReporterNotification($report));
        $admins = User::permission('aduan.adminTask')->get();
        $this->notifyGroup($admins, new ReportDoneAdminNotification($report));

        return $report;
    }
}
