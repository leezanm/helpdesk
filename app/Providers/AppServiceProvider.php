<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Use Bootstrap 5 pagination markup everywhere. Livewire defaults to a
        // Tailwind paginator whose inline SVG arrows render huge without
        // Tailwind; Bootstrap markup matches our theme/CSS.
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');

        // Livewire's WithPagination reads this to pick its paginator view.
        config(['livewire.pagination_theme' => 'bootstrap']);
    }
}
