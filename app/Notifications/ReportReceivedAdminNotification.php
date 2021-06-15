<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportReceivedAdminNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($report)
    {
        //
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
        $report = $this->report;
        return (new MailMessage)
        ->subject('KPLB Helpdesk - Aduan Baru Diterima')
        ->greeting('Salam Sejahtera,')
        ->line('Aduan baru memerlukan perhatian')
        ->line('Maklumat Aduan')
        ->line('No Tiket : '. $report->ticket_no)
        ->action('Lihat', url('/'));
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
            'url' => route('aduan.show', $report->id),
            'icon' => 'bx bx-mail-send',
            'color' => 'bg-info'
        ];
    }
}
