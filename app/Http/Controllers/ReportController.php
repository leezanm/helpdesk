<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\UpdateRequest;
use App\Models\Ref\ReportStatus;
use App\Models\Ref\TaskStatus;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\Task;
use App\Models\User;
use Exception;
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
        $reports = Report::whereIn('report_status_id', [2,3,4,5])->orderBy('submitted_at')->get();
        return view('aduan.index', compact('reports'));
    }

    public function indexUser()
    {
        //
        $reports = Report::where('report_by_id', Auth::user()->id)->orderBy('submitted_at', 'desc')->get();
        return view('aduan.index', compact('reports'));
    }

    public function indexDihantar()
    {
        //
        $reports = Report::whereIn('report_status_id', [2,3])->orderBy('submitted_at', 'desc')->get();
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
        $report->submitted_at = $request->report_status_id == 2 ? now() : null;
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

        $roles = Role::whereHas('permissions', function($query){
            $query->where('name', 'aduan.updateTask');
        })->where('name', '!=', 'superadmin')->get();

        $trails = $report->trails;
        $attachments = $report->attachments;
        $attachments->append('file_url');

        return view('aduan.show', compact('report', 'roles', 'trails', 'attachments'));
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
    public function update(UpdateRequest $request, Report $report)
    {
        //
        
        // switch($request->tindakan){
        //     case '1':
        //         $user = User::find($request->assigned_to_id);
        //         $this->assignOfficer($request, $report, $user);
        //         break;
        //     case '2':
        //         $user = $report->reportBy;
        //         $this->updateFinish($request,$report, $user);
        //         break;
        // }

        // $report->report_status_id = $request
        $user = User::find($request->assigned_to_id);
        $report->report_status_id = 4;

        $trail = new ReportTrail();
        $trail->message = 'Aduan telah dihantar kepada <a class="userProfile" data-userId="'. $user->id .'" role="button">'. $user->name . '</a> untuk disemak';
        $trail->assigned_to_id = $request->assigned_to_id;
        $trail->updated_by_id = Auth::user()->id;
        $trail->status_id = 4;

        $report->save();
        $report->trails()->save($trail);

        return redirect()->back();

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

    public function assignOfficer(Request $request,Report $report, User $user) : void{

        $report->report_status_id = 4;

        $trail = new ReportTrail();
        $trail->message = 'Aduan telah dihantar kepada <a class="userProfile" data-userId="'. $user->id .'" role="button">'. $user->name . '</a> untuk disemak';
        $trail->assigned_to_id = $request->assigned_to_id;
        $trail->updated_by_id = Auth::user()->id;
        $trail->status_id = 4;

        $report->save();
        $report->trails()->save($trail);

        try{

        } catch(Exception $e){}
    }

    public function updateFinish(Request $request,Report $report, User $user){

        $report->report_status_id = 5;

        $trail = new ReportTrail();
        $trail->message = 'Aduan telah ditanda selesai dengan <br></br>';
        $trail->assigned_to_id = $request->assigned_to_id;
        $trail->updated_by_id = Auth::user()->id;
        $trail->status_id = 4;

        $report->save();
        $report->trails()->save($trail);

        try{

        } catch(Exception $e){}
    }

}
