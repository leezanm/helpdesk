<?php

namespace App\Http\Controllers;

use App\Models\ReportTrail;
use Illuminate\Http\Request;

class ReportTrailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Models\ReportTrail  $reportTrail
     * @return \Illuminate\Http\Response
     */
    public function show(ReportTrail $reportTrail)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReportTrail  $reportTrail
     * @return \Illuminate\Http\Response
     */
    public function edit(ReportTrail $reportTrail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReportTrail  $reportTrail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReportTrail $reportTrail)
    {
        //
        $reportTrail->technical_remark = $request->technical_remark;
        $reportTrail->save();

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReportTrail  $reportTrail
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReportTrail $reportTrail)
    {
        //
    }
}
