<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsReportMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportDoneReporterNotification extends Notification implements ShouldQueue
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
        return ['mail'];
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
            'Sistem Meja Bantuan KKDW - Maklum Balas Aduan Berjaya Dihantar',
            'Maklum balas bagi aduan '.$this->report->ticket_no.' berjaya dihantar. Aduan kini Selesai.',
            withActionButton: false
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
        return [
            //
        ];
    }
}
