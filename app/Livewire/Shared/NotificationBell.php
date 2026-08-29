<?php

namespace App\Livewire\Shared;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Header notification bell. Replaces the @php block + dropdown that was inlined
 * in layouts/app.blade.php and the jQuery polling in notification.js.
 *
 * Polls for unread database notifications, shows the count + list, and marks a
 * notification read (then redirects to its url) when clicked.
 */
class NotificationBell extends Component
{
    /**
     * Mark a notification read and redirect to its stored url (mirrors
     * NotificationController::read).
     */
    public function open(string $notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if (! $notification) {
            return null;
        }

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url ? redirect($url) : redirect()->back();
    }

    protected function humanTime($createdAt): string
    {
        // Preserve the Bahasa Malaysia relative-time wording from the old code.
        $text = $createdAt->diffForHumans();
        $text = str_replace('detik', 'saat', $text);
        $text = str_replace('menit', 'minit', $text);

        return str_replace(' yang lalu', '', $text);
    }

    public function render()
    {
        $notifications = Auth::user()->unreadNotifications;

        $items = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'color' => $notification->data['color'] ?? 'bg-primary',
                'icon' => $notification->data['icon'] ?? 'bx bx-bell',
                'title' => $notification->data['title'] ?? '',
                'message' => $notification->data['message'] ?? '',
                'time' => $this->humanTime($notification->created_at),
            ];
        });

        return view('livewire.shared.notification-bell', [
            'items' => $items,
            'count' => $notifications->count(),
        ]);
    }
}
