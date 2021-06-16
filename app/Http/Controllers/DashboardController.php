<?php

namespace App\Http\Controllers;

use App\Models\fbi;
use App\Models\mbi;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $newUser = \DB::table('reports')->count();
        $mbiTotal = \DB::table('reports')->where('report_status_id', 5)->count();
        $fbiTotal = \DB::table('reports')->where('report_status_id', 4)->count();

        $currentDay = Carbon::today();
        $currentMonth = Carbon::today()->month;
        $currentYear = Carbon::today()->year;

        // $today = mbi::whereDate('created_at', $currentDay)->count() + fbi::whereDate('created_at', $currentDay)->count();
        // $thisMonth = mbi::whereMonth('created_at', $currentMonth)->count() + fbi::whereMonth('created_at', $currentMonth)->count();
        // $thisYear = mbi::whereYear('created_at', $currentYear)->count() + fbi::whereYear('created_at', $currentYear)->count();
        $today = 0;
        $thisMonth = 0;
        $thisYear = 0;

        $reports_query = Report::query();

        $user = Auth::user();

        if($user->hasPermissionTo('aduan.assignTask')){
            $reports_query->whereIn('report_status_id', [2,3]);
        } elseif($user->hasPermissionTo('aduan.updateTask')){
            $reports_query->whereIn('report_status_id', [4])
            ->join('report_trails', 'reports.id', 'report_trails.report_id')
            ->select('reports.*', 'report_trails.id as trail_id', 'assigned_to_id')
            ->where('report_trails.id', \DB::raw('(select id from report_trails where report_id = reports.id order by id desc limit 1)'))
            ->where('assigned_to_id', Auth::user()->id);
        } else {
            $reports_query->where('report_by_id', $user->id)->whereNull('completed_at');
        }

        $reports = $reports_query->orderBy('submitted_at', 'desc')->get();

        return view('dashboard', compact('newUser', 'mbiTotal', 'fbiTotal', 'today', 'thisMonth', 'thisYear', 'reports'));

    }
}
