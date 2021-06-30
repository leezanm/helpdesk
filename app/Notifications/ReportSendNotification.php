<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportSendNotification extends Notification
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
        $report =$this->report;
        return (new MailMessage)
                    ->subject('KPLB Helpdesk - Aduan Berjaya Dihantar')
                    ->greeting('Salam Sejahtera,')
                    ->line('Aduan anda berjaya dihantar')
                    ->line('Maklumat Aduan')
                    ->line('No Tiket : '. $report->ticket_no)
                    ->action('Lihat', route('aduan.show', $report->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $report =$this->report;
        return [
            //
            'title' => 'Aduan Berjaya Dihantar',
            'message' => 'Aduan anda berjaya dihantar. No tiket aduan adalah '. $report->ticket_no,
            'url' => route('aduan.show', $report->id),
            'icon' => 'bx bxs-paper-plane',
            'color' => 'bg-success'
        ];
    }
}
