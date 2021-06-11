<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
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
    public function store(Request $request, Report $report)
    {
        //
        $report->report_status_id = 3;
        $report->save();

        $task = new Task();
        $task->assigned_by_id = Auth::user()->id;
        $task->assigned_to_id = $request->assigned_to_id;
        $task->assigned_at = now();
        $task->task_status_id = 1;

        $report->tasks()->save($task);

        return redirect()->route('aduan.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Task $task)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function edit(Task $task)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        //
        $report = $task->report;
        $task->task_status_id = $request->task_status_id;

        if($task->isDirty('task_status_id')){
            $new_task = $task->replicate();
            $new_task->task_status_id = $request->task_status_id;

            if(in_array($request->task_status_id, [3,4])){
                switch($request->task_status_id){
                    case 3 :
                        $report->report_status_id = 4;
                        $report->completed_at = now();
                        break;
                    case 4 :
                        $new_task->remark = $request->remark;
                        $report->report_status_id = 2;
                        break;
                }
                $report->save();
            }
            $new_task->save();
        }
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        //
    }
}
