<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * After login, force any user whose phone number (tel_no) is empty to update
 * it before using the system. The user is kept on the dashboard where the
 * profile modal (#edit-profile) auto-opens and is locked until tel_no is set.
 *
 * A small set of routes is exempt so the user can actually fix it (submit the
 * profile update) and leave (logout) without a redirect loop.
 */
class EnsurePhoneFilled
{
    /** Route names that must remain reachable while phone is missing. */
    protected array $except = [
        'account.profile.update',   // the save endpoint itself
        'account.password.update',
        'logout',
        'dashboard.index',          // where the modal is shown
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && blank($user->tel_no)) {
            // Let the exempt routes through (including the modal's save POST).
            if (! $this->isExcept($request)) {
                return redirect()->route('dashboard.index');
            }

            // Tell the layout to auto-open + lock the profile modal.
            View::share('forceProfileUpdate', true);
        }

        return $next($request);
    }

    protected function isExcept(Request $request): bool
    {
        $name = optional($request->route())->getName();
        if ($name && in_array($name, $this->except, true)) {
            return true;
        }

        // Fortify's logout has no name in some setups; match the path too.
        return $request->is('logout');
    }
}
