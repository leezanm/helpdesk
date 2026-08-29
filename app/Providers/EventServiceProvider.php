<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\User;
use App\Observers\ReportObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
        User::observe(UserObserver::class);
        Report::observe(ReportObserver::class);

        // Security logging: record failed logins and lockouts so suspicious
        // activity (brute force / credential stuffing) is auditable in the logs.
        Event::listen(Failed::class, function (Failed $event) {
            Log::channel(config('logging.default'))->warning('Login gagal', [
                'username' => $event->credentials['username'] ?? null,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        Event::listen(Lockout::class, function (Lockout $event) {
            Log::channel(config('logging.default'))->warning('Login lockout (terlalu banyak percubaan)', [
                'username' => $event->request->input('username'),
                'ip' => $event->request->ip(),
                'user_agent' => $event->request->userAgent(),
            ]);
        });
    }
}
