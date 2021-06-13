<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LaporanController extends Controller
{
    //
    public function index(){

        return view('laporan.index');
    }


    //return response json
    public function laporanByStatusApi(Request $request){

        $laporan =  \DB::table('_report_statuses as report_status')
                    ->select('id', 'desc')
                    ->selectRaw('ifnull((select count(*) from reports where report_status_id = report_status.id group by report_status_id),0) as jumlah')
                    ->whereIn('id', [2,3,4,5])
                    ->orderBy('id')
                    ->get();
        $datas =[
            'success' => true,
            'laporan' => $laporan
        ];
        return response()->json($datas);
    }

    public function laporanByCategoryApi(Request $request){

        $laporan =  \DB::table('report_categories as category')
                    ->select('desc')
                    ->selectRaw('ifnull((select count(*) from reports where report_category_id = category.id and report_status_id in (2,3,4,5) group by report_category_id),0) as jumlah')
                    ->orderBy('desc', 'asc')
                    ->get();
        $datas =[
            'success' => true,
            'laporan' => $laporan
        ];
        return response()->json($datas);
    }

    public function laporanByRoleyApi(Request $request){

        $laporan =  \DB::table('report_categories as category')
                    ->select('desc')
                    ->selectRaw('ifnull((select count(*) from reports where report_category_id = category.id group by report_category_id),0) as jumlah')
                    ->orderBy('desc', 'asc')
                    ->get();
        $datas =[
            'success' => true,
            'laporan' => $laporan
        ];
        return response()->json($datas);
    }
}
