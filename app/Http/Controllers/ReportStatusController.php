<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportStatus\UpdateRequest;
use App\Models\Ref\ReportStatus;
use Illuminate\Http\Request;

class ReportStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $report_statuses = ReportStatus::orderBy('id')->get();
        return view('status.index', compact('report_statuses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Ref\ReportStatus  $reportStatus
     * @return \Illuminate\Http\Response
     */
    public function show(ReportStatus $reportStatus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Ref\ReportStatus  $reportStatus
     * @return \Illuminate\Http\Response
     */
    public function edit(ReportStatus $reportStatus)
    {
        //
        $datas = [
            'success' => true,
            'object' => $reportStatus
        ];

        return response()->json($datas);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ref\ReportStatus  $reportStatus
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, ReportStatus $reportStatus)
    {
        //
        $reportStatus->desc = $request->desc;
        $reportStatus->save();

        $datas = [
            'success' => true
        ];

        return response()->json($datas);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Ref\ReportStatus  $reportStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReportStatus $reportStatus)
    {
        //
    }
}
