<?php

namespace App\Http\Controllers;

use App\Models\ReportCategory;
use Exception;
use Illuminate\Http\Request;
use Str;
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
        $report_categories = ReportCategory::orderBy('desc')->get()->append('image');
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

        $reportCategory = new ReportCategory();
        $reportCategory->fill($request->except('file'));

        if($request->hasFile('file')){

            $path = $request->file('file')->store('public/images/categories');
            $reportCategory->image_path = Str::of($path)->replace('public', 'storage');
        }

        $reportCategory->save();

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

        if($reportCategory->is_active){
            $status = '<span class="badge bg-success">Aktif</span>';
        } else {
            $status = '<span class="badge bg-danger">Tidak Aktif</span>';
        }

        $reportCategory->append('image');
        $reportCategory->setAttribute('status', $status);
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

        $reportCategory->fill($request->except('file'));

        if($request->hasFile('file')){

            $path = $request->file('file')->store('public/images/categories');
            $reportCategory->image_path = Str::of($path)->replace('public', 'storage');
        }

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
