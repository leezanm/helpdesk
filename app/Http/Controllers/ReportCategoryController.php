<?php

namespace App\Http\Controllers;

/**
 * Report category management.
 *
 * CRUD is handled by Livewire components (App\Livewire\Category\CategoryIndex
 * and CategoryForm). This controller now only renders the page wrapper, which
 * mounts the Livewire index component.
 */
class ReportCategoryController extends Controller
{
    /**
     * Display the category management page (mounts the Livewire component).
     */
    public function index()
    {
        return view('category.index');
    }
}
