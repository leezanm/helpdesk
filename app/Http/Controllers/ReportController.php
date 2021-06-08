<?php

namespace App\Http\Controllers;

use App\Models\Ref\ReportStatus;
use App\Models\Ref\TaskStatus;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportCategory;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $reports = Report::orderBy('submitted_at')->get();
        return view('aduan.index', compact('reports'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $report_categories = ReportCategory::orderBy('desc')->get();
        return view('aduan.create', compact('report_categories'));
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
        $report = new Report();
        $report->report_by_id = Auth::user()->id;
        $report->remark = $request->remark;
        $report->subject = $request->subject;
        $report->report_status_id = $request->report_status_id;
        $report->report_category_id = $request->report_category_id;
        $report->submitted_at = $request->report_status_id == 2? now() : null;
        $report->save();

        
        if(filled($request->attachment_paths)){
            $report_attachments = [];
            foreach($request->attachment_paths as $index => $path){
                $report_attachment = new ReportAttachment();
                $report_attachment->name = $request->attachment_names[$index];
                $report_attachment->path = $path;
                $report_attachment->type = $request->attachment_types[$index];

                array_push($report_attachments, $report_attachment);
            }
            $report->attachments()->saveMany($report_attachments);
        }

        return redirect()->route('aduan.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function show(Report $report)
    {
        //

        $tasks = $report->tasks;
        $current_task = $report->currentTask;

        $roles = Role::whereHas('permissions', function($query){
            $query->where('name', 'aduan.updateTask');
        })->where('name', '!=', 'superadmin')->get();

        $task_statuses = TaskStatus::orderBy('id')->get();

        return view('aduan.show', compact('report', 'roles', 'tasks', 'current_task', 'task_statuses'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Report $report)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Report $report)
    {
        //
    }

}
