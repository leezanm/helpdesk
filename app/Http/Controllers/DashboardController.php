<?php

namespace App\Http\Controllers;

use App\Models\fbi;
use App\Models\mbi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $newUser = User::all()->count();
        // $mbiTotal = mbi::all()->count();
        // $fbiTotal = fbi::all()->count();
        $mbiTotal = 0;
        $fbiTotal = 0;

        $currentDay = Carbon::today();
        $currentMonth = Carbon::today()->month;
        $currentYear = Carbon::today()->year;

        // $today = mbi::whereDate('created_at', $currentDay)->count() + fbi::whereDate('created_at', $currentDay)->count();
        // $thisMonth = mbi::whereMonth('created_at', $currentMonth)->count() + fbi::whereMonth('created_at', $currentMonth)->count();
        // $thisYear = mbi::whereYear('created_at', $currentYear)->count() + fbi::whereYear('created_at', $currentYear)->count();
        $today = 0;
        $thisMonth = 0;
        $thisYear = 0;

        return view('dashboard', compact('newUser', 'mbiTotal', 'fbiTotal', 'today', 'thisMonth', 'thisYear'));

    }
}
