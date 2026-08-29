<?php

namespace App\Actions\Reports;

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\User;
use App\Notifications\ReportReceivedAdminNotification;
use App\Notifications\ReportSendNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Create or update a report (aduan), persisting attachments and sending
 * notifications when the report is submitted (status 2).
 *
 * Extracted from ReportController::store/update. The ReportObserver still
 * handles ticket_no generation and first-trail creation on save/created.
 *
 * @param array<int,\Illuminate\Http\UploadedFile> $attachments newly uploaded files
 */
class SaveReport
{
    use NotifiesReport;

    /**
     * @param  array<string,mixed>  $data  subject, remark, report_category_id, report_status_id, is_bp, bp_* fields
     * @param  array<int,\Illuminate\Http\UploadedFile>  $attachments
     */
    public function handle(Report $report, array $data, array $attachments = []): Report
    {
        $statusId = (int) $data['report_status_id'];

        $report->is_bp = (bool) ($data['is_bp'] ?? false);
        $report->report_by_id = Auth::id();
        $report->bahagian_pengadu = Auth::user()->ldap_bahagian;
        $report->remark = $data['remark'];
        $report->subject = htmlentities($data['subject'], ENT_QUOTES, 'UTF-8');
        $report->report_status_id = $statusId;
        $report->report_category_id = $data['report_category_id'];
        $report->submitted_at = $statusId === 2 ? now() : null;

        if ($report->is_bp) {
            $report->bp_username = $data['bp_username'] ?? null;
            $report->bp_name = $data['bp_name'] ?? null;
            $report->bp_bahagian = $data['bp_bahagian'] ?? null;
            $report->bp_email = $data['bp_email'] ?? null;
            $report->bp_user_id = $data['bp_user_id'] ?? null;
        }

        $report->save();

        $this->storeAttachments($report, $attachments);

        if ($statusId === 2) {
            $this->notifySingle(Auth::user(), new ReportSendNotification($report));
            $admins = User::permission('aduan.adminTask')->get();
            $this->notifyGroup($admins, new ReportReceivedAdminNotification($report));
        }

        return $report;
    }

    /**
     * @param  array<int,\Illuminate\Http\UploadedFile>  $attachments
     */
    protected function storeAttachments(Report $report, array $attachments): void
    {
        if (empty($attachments)) {
            return;
        }

        $rows = [];
        foreach ($attachments as $file) {
            $path = $file->store('public/aduan/attachments');

            $attachment = new ReportAttachment();
            $attachment->name = $file->getClientOriginalName();
            $attachment->path = (string) Str::of($path)->replace('public', 'storage');
            $attachment->type = $file->getClientOriginalExtension();
            $rows[] = $attachment;
        }

        $report->attachments()->saveMany($rows);
    }
}
