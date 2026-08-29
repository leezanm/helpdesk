<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportAssignedNotification extends Notification
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
        $this->report =$report;
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
        ->subject('Sistem Meja Bantuan KKDW - Tugasan Baru')
        ->greeting('Salam Sejahtera,')
        ->line('Aduan baru telah ditugaskan kepada anda')
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
        $report = $this->report;
        return [
            //
            'title' => 'Tugasan Baru',
            'message' => 'Aduan bernombor '. $report->ticket_no . ' telah ditugaskan kepada anda',
            'url' => route('aduan.show', $report->id),
            'icon' => 'bx bx-task',
            'color' => 'bg-warning'
        ];
    }
}
