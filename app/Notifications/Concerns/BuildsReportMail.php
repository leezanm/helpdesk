<?php

namespace App\Notifications\Concerns;

use App\Models\Report;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Shared builder so every aduan email has the same rich body: complaint
 * details, complainant contact (bahagian / email / phone), a signed read-only
 * "Lihat Aduan" preview link (no login), and a "Log Masuk untuk Tindakan"
 * button that goes to login and then straight to the aduan (via Laravel's
 * intended-URL redirect after login).
 */
trait BuildsReportMail
{
    /**
     * @param  string  $subject   email subject
     * @param  string  $intro     one-line intro under the greeting
     * @param  bool    $withActionButton  include the "Log Masuk untuk Tindakan" button
     */
    protected function buildReportMail(Report $report, string $subject, string $intro, bool $withActionButton = true): MailMessage
    {
        $report->loadMissing(['category', 'status', 'reportBy']);

        // Signed, 7-day, read-only preview — openable without login.
        $previewUrl = URL::temporarySignedRoute(
            'aduan.public-view',
            now()->addDays(7),
            ['report' => $report]
        );

        // Protected aduan page. A guest clicking this is sent to login, then
        // redirected straight here after logging in (auth intended URL).
        $actionUrl = route('aduan.show', $report);

        $pengaduName = $report->reportBy->name ?? ($report->bp_name ?? '-');
        $bahagian = $report->bahagian_pengadu ?: ($report->reportBy->ldap_bahagian ?? '-');
        $email = $report->reportBy->email ?? '-';
        $tel = $report->reportBy->tel_no ?? '-';
        $remark = Str::limit(strip_tags((string) $report->remark), 400);

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Salam Sejahtera,')
            ->line($intro)
            ->line('**Maklumat Aduan**')
            ->line('No. Tiket: '.$report->ticket_no)
            ->line('Status: '.($report->status->desc ?? '-'))
            ->line('Subjek: '.$report->subject)
            ->line('Kategori: '.($report->category->desc ?? '-'))
            ->line('Butiran Kerosakan: '.($remark !== '' ? $remark : '-'))
            ->line('**Maklumat Pengadu**')
            ->line('Nama: '.$pengaduName)
            ->line('Bahagian: '.$bahagian)
            ->line('Emel: '.$email)
            ->line('No. Telefon: '.$tel)
            ->action('Lihat Aduan', $previewUrl);

        if ($withActionButton) {
            $mail->line('Untuk mengambil tindakan atau mengemaskini status, sila log masuk:')
                ->line('[Log Masuk untuk Tindakan]('.$actionUrl.')');
        }

        $mail->line('Pautan "Lihat Aduan" membuka pratonton aduan (rujukan sahaja) tanpa perlu log masuk.');

        return $mail;
    }
}
