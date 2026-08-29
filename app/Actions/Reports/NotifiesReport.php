<?php

namespace App\Actions\Reports;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Shared notification helpers, extracted from ReportController::notifySingle /
 * notifyGroup. Failures are logged, never fatal (matches prior behavior).
 */
trait NotifiesReport
{
    protected function notifySingle($user, $notification): void
    {
        if (filled($user)) {
            try {
                $user->notify($notification);
            } catch (Exception $e) {
                Log::error($e);
            }
        }
    }

    protected function notifyGroup($users, $notification): void
    {
        if (filled($users)) {
            try {
                Notification::send($users, $notification);
            } catch (Exception $e) {
                Log::error($e);
            }
        }
    }
}
