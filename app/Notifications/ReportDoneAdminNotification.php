<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportDoneAdminNotification extends Notification
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
        $report = $this->report;
        return (new MailMessage)
        ->subject('Sistem Meja Bantuan KPLB - Aduan Selesai')
        ->greeting('Salam Sejahtera,')
        ->line('Aduan telah ditanda selesai oleh pengguna')
        ->line('Klik padu butang \'Lihat\' untuk lihat maklum balas pengadu')
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
        return [
            //
        ];
    }
}
