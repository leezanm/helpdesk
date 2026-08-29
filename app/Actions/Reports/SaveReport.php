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
        $report->remark = $data['remark'];
        $report->subject = htmlentities($data['subject'], ENT_QUOTES, 'UTF-8');
        $report->report_status_id = $statusId;
        $report->report_category_id = $data['report_category_id'];
        $report->submitted_at = $statusId === 2 ? now() : null;

        if ($report->is_bp) {
            // Represented aduan. The core rule: the aduan is recorded UNDER the
            // represented person (pengadu) — so bahagian_pengadu ALWAYS reflects
            // the represented person's bahagian, never the wakil's. This fixes
            // the bug where a fault in another bahagian was recorded under the
            // wakil's bahagian.
            $represented = ! empty($data['bp_user_id'])
                ? User::find($data['bp_user_id'])
                : null;

            $report->bahagian_pengadu = $represented?->ldap_bahagian
                ?? ($data['bp_bahagian'] ?? Auth::user()->ldap_bahagian);

            if ($represented) {
                // Represented person exists as a local user: they become the
                // owner (report_by_id). The acting wakil is kept in bp_* so the
                // "Aduan Saya" scope (report_by_id OR bp_user_id) still shows the
                // aduan to the wakil and records who filed it.
                $report->report_by_id = $represented->id;
                $report->bp_user_id = Auth::id();
                $report->bp_username = Auth::user()->username;
                $report->bp_name = Auth::user()->name;
                $report->bp_bahagian = Auth::user()->ldap_bahagian;
                $report->bp_email = Auth::user()->email;
            } else {
                // Represented person is AD-only (no local user record). We cannot
                // point report_by_id at a non-existent user, so the acting user
                // remains report_by_id, and the represented person's identity is
                // stored in bp_* (as before). bahagian_pengadu is still correct.
                $report->report_by_id = Auth::id();
                $report->bp_user_id = null;
                $report->bp_username = $data['bp_username'] ?? null;
                $report->bp_name = $data['bp_name'] ?? null;
                $report->bp_bahagian = $data['bp_bahagian'] ?? null;
                $report->bp_email = $data['bp_email'] ?? null;
            }
        } else {
            // Direct aduan: the logged-in user is the pengadu.
            $report->report_by_id = Auth::id();
            $report->bahagian_pengadu = Auth::user()->ldap_bahagian;
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
