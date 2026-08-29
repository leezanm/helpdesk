<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Support\Facades\Auth;

/**
 * Report (aduan) management.
 *
 * The interactive CRUD/flow is handled by Livewire components
 * (App\Livewire\Aduan\ReportIndex / ReportForm / ReportShow). This controller
 * renders the page wrappers that mount those components and supplies the
 * listing scope for each index variant.
 */
class ReportController extends Controller
{
    public function index()
    {
        return view('aduan.index', ['scope' => 'all', 'username' => null]);
    }

    public function indexUser()
    {
        return view('aduan.index', ['scope' => 'user', 'username' => Auth::user()->username]);
    }

    public function indexDihantar()
    {
        return view('aduan.index', ['scope' => 'dihantar', 'username' => null]);
    }

    public function indexUntukTindakan()
    {
        return view('aduan.index', ['scope' => 'tindakan', 'username' => null]);
    }

    public function create()
    {
        return view('aduan.create');
    }

    public function edit(Report $report)
    {
        return view('aduan.edit', compact('report'));
    }

    public function show(Report $report)
    {
        return view('aduan.show', compact('report'));
    }

    /**
     * Lesson-learn knowledge base (mounts the Livewire component).
     */
    public function lessonLearn()
    {
        return view('aduan.lesson_learn');
    }
}
