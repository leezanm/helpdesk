<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Models\Report::class => \App\Policies\ReportPolicy::class,
        \App\Models\Movement::class => \App\Policies\MovementPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });

        // Who may access the org-wide "Dashboard Utama": management roles only
        // (superadmin is already covered by the Gate::before hook above).
        Gate::define('access-main-dashboard', function ($user) {
            return $user->hasAnyRole(['helpdesk.admin', 'penyelia', 'pengurusan']);
        });
    }
}
