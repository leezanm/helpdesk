<?php

namespace App\Http\Controllers;

/**
 * Report status management.
 *
 * Editing is handled by the Livewire component App\Livewire\Status\StatusIndex.
 * This controller only renders the page wrapper that mounts it.
 */
class ReportStatusController extends Controller
{
    /**
     * Display the status management page (mounts the Livewire component).
     */
    public function index()
    {
        return view('status.index');
    }
}
