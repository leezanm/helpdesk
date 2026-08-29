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
        $report = $this->report->loadMissing(['category', 'reportBy']);

        // Signed, expiring (7-day), read-only preview link — openable from the
        // email without logging in. Taking action still requires login.
        $previewUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'aduan.public-view',
            now()->addDays(7),
            ['report' => $report]
        );

        $remark = \Illuminate\Support\Str::limit(strip_tags((string) $report->remark), 300);

        return (new MailMessage)
            ->subject('Sistem Meja Bantuan KKDW - Tugasan Baru: '.$report->ticket_no)
            ->greeting('Salam Sejahtera,')
            ->line('Satu aduan baru telah ditugaskan kepada anda untuk tindakan. Berikut adalah maklumat ringkas aduan:')
            ->line('**No. Tiket:** '.$report->ticket_no)
            ->line('**Subjek:** '.$report->subject)
            ->line('**Kategori:** '.($report->category->desc ?? '-'))
            ->line('**Bahagian:** '.($report->bahagian_pengadu ?: '-'))
            ->line('**Pengadu:** '.($report->reportBy->name ?? '-')
                .($report->reportBy?->email ? ' ('.$report->reportBy->email.')' : ''))
            ->line('**Ringkasan:** '.($remark !== '' ? $remark : '-'))
            ->action('Lihat Butiran Aduan', $previewUrl)
            ->line('Pautan di atas membuka pratonton aduan (rujukan sahaja). Untuk mengambil tindakan atau mengemaskini status, sila log masuk ke sistem.');
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
