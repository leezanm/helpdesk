<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\User;

use App\Notifications\AppNotification;

use Illuminate\Support\Facades\Auth;

use PDF;

class NotificationController extends Controller
{
    //

    public function listNotification()
    {
        $user = Auth::user();

        $notifications = $user->unreadNotifications;
        foreach ($notifications as $notification) {
            $created_at_hr = $notification->created_at->diffForHumans();
            $created_at_hr = str_replace('detik', 'saat', $created_at_hr);
            $created_at_hr = str_replace('menit', 'minit', $created_at_hr);
            $created_at_hr = str_replace(' yang lalu', '', $created_at_hr);
            $notification->setAttribute('created_at_hr', $created_at_hr);
        }
        $datas = [
            'success' => true,
            'notifications' => $notifications,
            'total' => $notifications->count()
        ];
        return response()->json($datas);
    }

    public function read(Request $request, $notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications->find($notificationId);
        $notification->markAsRead();
        if (filled($notification->data['url'])) {
            return redirect($notification->data['url']);
        }
        return redirect()->back()->with('errorMessage', 'Url tidak wujud');
    }

    public function readAll(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->unreadNotifications()->where('type', 'App\\Notifications\\AppNotification')->update(['read_at' => now()]);
        $datas = [
            'success' => true
        ];
        return response()->json($datas);
    }

}
