<?php

namespace App\Http\Controllers;

/**
 * Dashboard.
 *
 * The dashboard is a Livewire component (App\Livewire\Dashboard\Dashboard).
 * This controller renders the page wrapper that mounts it. Chart data is still
 * served by App\Http\Controllers\Api\DashboardController.
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }
}
