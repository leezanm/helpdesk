<?php

namespace App\Http\Controllers;

/**
 * Risk level (tahap risiko) maintenance.
 *
 * Editing is handled by the Livewire component App\Livewire\RiskLevel\RiskLevelIndex.
 * This controller only renders the page wrapper that mounts it.
 */
class RiskLevelController extends Controller
{
    /**
     * Display the risk level management page (mounts the Livewire component).
     */
    public function index()
    {
        return view('risk_level.index');
    }
}
