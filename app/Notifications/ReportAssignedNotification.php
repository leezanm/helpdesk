<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsReportMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportAssignedNotification extends Notification implements ShouldQueue
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
            'Sistem Meja Bantuan KKDW - Tugasan Baru: '.$this->report->ticket_no,
            'Satu aduan telah ditugaskan untuk tindakan. Berikut adalah maklumat aduan:'
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
            'title' => 'Tugasan Baru',
            'message' => 'Aduan bernombor '. $report->ticket_no . ' telah ditugaskan kepada anda',
            'url' => route('aduan.show', $report),
            'icon' => 'bx bx-task',
            'color' => 'bg-warning'
        ];
    }
}
