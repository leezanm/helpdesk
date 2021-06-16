<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportCategory;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function pieChart(Request $request){

        $weekStartDate = now()->startOfWeek()->format('Y-m-d H:i');
        $weekEndDate = now()->endOfWeek()->format('Y-m-d H:i');

        $report_categories =  \DB::table('report_categories as category')
                    ->select('desc as name')
                    ->selectRaw('ifnull((select count(*) from reports where report_category_id = category.id and report_status_id in (2,3,4,5) and submitted_at >= ?  group by report_category_id),0) as value', [$weekStartDate])
                    ->orderBy('desc', 'asc')
                    ->having('value', '>', 0)
                    ->get();

        $response = [
            'success' => true,
            'data' => $report_categories
        ];

        return response()->json($response);
    }

    public function barChart(Request $request){

        $weekStartDate = now()->startOfWeek()->format('Y-m-d H:i');
        $weekEndDate = now()->endOfWeek()->format('Y-m-d H:i');

        $periods = collect( CarbonPeriod::create($weekStartDate, $weekEndDate) );
        $periods->transform(function($period){
            return $period->format('Y-m-d');
        });

        $count_sent_by_day = [];
        $count_completed_by_day = [];

        $reports_send = \DB::table('reports')
                        ->selectRaw('count(*) as total, date(submitted_at) as date')
                        ->whereDate('submitted_at', '>=', $weekStartDate)
                        ->whereDate('submitted_at', '<=', $weekEndDate)
                        ->groupBy('date')
                        ->pluck('total','date');

        $reports_completed = \DB::table('reports')
                        ->selectRaw('count(*) as total, date(completed_at) as date')
                        ->whereDate('completed_at', '>=', $weekStartDate)
                        ->whereDate('completed_at', '<=', $weekEndDate)
                        ->groupBy('date')
                        ->pluck('total','date');

        foreach ($periods as $period) {
            $total = $reports_send->has($period) ? $reports_send[$period] : 0;
            array_push($count_sent_by_day, $total);
        }

        foreach ($periods as $period) {
            $total = $reports_completed->has($period) ? $reports_completed[$period] : 0;
            array_push($count_completed_by_day, $total);
        }

        $data= [
            'sent' => $count_sent_by_day,
            'completed' => $count_completed_by_day
        ];

        $response = [
            'success' => true,
            'data' => $data
        ];

        return response()->json($response);
    }

    public function categoryTable(Request $request){

        $weekStartDate = now()->startOfWeek()->format('Y-m-d H:i');

        $category_by_status = \DB::table('report_categories')
                                ->select('desc', 'image_path')
                                ->selectRaw('( select count(*) from reports where reports.report_category_id = report_categories.id and report_status_id = 2 and submitted_at >= ?) as dihantar', [$weekStartDate])
                                ->selectRaw('( select count(*) from reports where reports.report_category_id = report_categories.id and report_status_id = 3 and submitted_at >= ?) as dihantar_ambil_maklum', [$weekStartDate])
                                ->selectRaw('( select count(*) from reports where reports.report_category_id = report_categories.id and report_status_id = 4 and submitted_at >= ?) as sedang_disemak', [$weekStartDate])
                                ->selectRaw('( select count(*) from reports where reports.report_category_id = report_categories.id and report_status_id = 5 and submitted_at >= ?) as selesai', [$weekStartDate])
                                ->where('is_active', true)
                                ->get();

        $category_by_status->transform(function($item, $key){
            $item->image_path = file_exists($item->image_path)? asset($item->image_path) : null;
            return $item;
        });

        $response = [
            'success' => true,
            'category_by_status' => $category_by_status
        ];

        return response()->json($response);
    }
}
