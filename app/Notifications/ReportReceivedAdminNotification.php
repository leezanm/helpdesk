<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsReportMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportReceivedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use BuildsReportMail;

    public $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return $this->buildReportMail(
            $this->report,
            'Sistem Meja Bantuan KKDW - Aduan Baru Diterima',
            'Satu aduan baru telah diterima dan memerlukan perhatian. Berikut adalah maklumat aduan:'
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $report = $this->report;
        return [
            //
            'title' => 'Aduan Baru Diterima',
            'message' => 'Aduan baru memerlukan perhatian. No tiket aduan : '. $report->ticket_no,
            'url' => route('aduan.show', $report),
            'icon' => 'bx bx-mail-send',
            'color' => 'bg-info'
        ];
    }
}
