<?php

namespace App\Http\Controllers;

use App\Models\ReportAttachment;
use Illuminate\Http\Request;
use Str;

class ReportAttachmentController extends Controller
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

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('public/aduan/attachments');
            $file_path = Str::of($path)->replace('public', 'storage');
        }

        return response()->json([
            'path' => $file_path,
            'name' => $request->file('file')->getClientOriginalName(),
            'type' => $request->file('file')->extension(),
        ]);
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ReportAttachment  $reportAttachment
     * @return \Illuminate\Http\Response
     */
    public function show(ReportAttachment $reportAttachment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReportAttachment  $reportAttachment
     * @return \Illuminate\Http\Response
     */
    public function edit(ReportAttachment $reportAttachment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReportAttachment  $reportAttachment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReportAttachment $reportAttachment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReportAttachment  $reportAttachment
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReportAttachment $reportAttachment)
    {
        //
        $reportAttachment->delete();
        return response()->json(['success' => true]);
    }
}
