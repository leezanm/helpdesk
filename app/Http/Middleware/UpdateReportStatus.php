<?php

namespace App\Http\Middleware;

use App\Models\ReportTrail;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateReportStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $report = $request->report;
        $user = Auth::user();

        if($report->report_status_id == 2 && $user->hasPermissionTo('aduan.adminTask')){
            $report->report_status_id = 3;
            $report->save();

            $trail = new ReportTrail();
            $trail->message = 'Aduan telah di ambil maklum oleh admin Sistem Meja Bantuan KKDW';
            $trail->updated_by_id = Auth::user()->id;
            $trail->status_id = 3;

            $report->trails()->save($trail);
        }

        return $next($request);
    }
}
