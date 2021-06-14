<?php

namespace App\Http\Controllers;

use App\Models\ReportCategory;
use Exception;
use Illuminate\Http\Request;

class ReportCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $report_categories = ReportCategory::orderBy('desc')->get();
        return view('category.index', compact('report_categories'));
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
        $request->validate([
            'desc' => 'required'
        ]);

        $type = new ReportCategory();
        $type->fill($request->all());
        $type->save();

        return response()->json(['success' => true]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ReportCategory  $reportCategory
     * @return \Illuminate\Http\Response
     */
    public function show(ReportCategory $reportCategory)
    {
        //
        $datas = [
            'success' => true,
            'data' => $reportCategory
        ];

        return response()->json($datas);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReportCategory  $reportCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(ReportCategory $reportCategory)
    {
        //
        $datas = [
            'success' => true,
            'data' => $reportCategory
        ];

        return response()->json($datas);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReportCategory  $reportCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReportCategory $reportCategory)
    {
        //
        $request->validate([
            'desc' => 'required'
        ]);

        $reportCategory->fill($request->all());
        $reportCategory->save();

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReportCategory  $reportCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReportCategory $reportCategory)
    {
        //
        $reportCategory->is_active = false;
        $reportCategory->save();

        return response()->json(['success' => true]);
    }
}
