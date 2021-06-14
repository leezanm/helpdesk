<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\AssignTaskRequest;
use App\Http\Requests\Report\StoreRequest;
use App\Http\Requests\Report\UpdateRequest;
use App\Models\Ref\ReportStatus;
use App\Models\Ref\TaskStatus;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportCategory;
use App\Models\ReportTrail;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReportAssignedNotification;
use App\Notifications\ReportReceivedAdminNotification;
use App\Notifications\ReportSendNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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
        $report_categories = ReportCategory::active()->orderBy('desc')->get();
        return view('aduan.create', compact('report_categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        //

        $report = new Report();
        $report->is_bp = $request->is_bp ;
        $report->report_by_id = Auth::user()->id;
        $report->remark = $request->remark;
        $report->subject = $request->subject;
        $report->report_status_id = $request->report_status_id;
        $report->report_category_id = $request->report_category_id;
        $report->submitted_at = $request->report_status_id == 2 ? now() : null;

        if($report->is_bp){
            $report->fill($request->only('bp_username', 'bp_name', 'bp_bahagian', 'bp_email'));
        }

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

        if( $report->report_status_id == 2){
            $report_sent_notification = new ReportSendNotification($report);
            $this->notifySingle(Auth::user(), $report_sent_notification);

            $report_received_admin_notification = new ReportReceivedAdminNotification($report);
            $users = User::permission('aduan.assignTask')->get();
            $this->notifyGroup($users, $report_received_admin_notification);
        }

        return redirect()->route('aduan.index-user', Auth::user()->id);
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
        $report_categories = ReportCategory::active()->orderBy('desc')->get();
        return view('aduan.edit', compact('report', 'report_categories'));
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

        if( $report->report_status_id == 2){
            $report_sent_notification = new ReportSendNotification($report);
            $this->notifySingle(Auth::user(), $report_sent_notification);

            $report_received_admin_notification = new ReportReceivedAdminNotification();
            $users = User::permission('aduan.assignTask')->get();
            $this->notifyGroup($users, $report_received_admin_notification);
        }

        return redirect()->route('aduan.index-user', Auth::user()->id);

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

    public function assignTask(AssignTaskRequest $request, Report $report)
    {

        $user = User::find($request->assigned_to_id);
        $report->report_status_id = 4;

        $trail = new ReportTrail();
        $trail->message = 'Aduan telah dihantar kepada <a class="userProfile" data-userId="'. $user->id .'" role="button">'. $user->name . '</a> untuk disemak';
        $trail->assigned_to_id = $request->assigned_to_id;
        $trail->updated_by_id = Auth::user()->id;
        $trail->status_id = 4;

        $report->save();
        $report->trails()->save($trail);

        $notification = new ReportAssignedNotification($report);
        $this->notifySingle($user,$notification);

        return redirect()->back();

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

    public function updateFeedback(Request $request,Report $report){

        $report->report_status_id = 5;
        $report->completed_at = now();
        $report->rating = $request->rating;
        $report->feedback = $request->feedback;

        $trail = new ReportTrail();
        $trail->message = 'Aduan telah ditanda selesai dengan <br> <b>Rating</b> <br> '. $request->rating. ' <br> <b>Komen</b> <br> '. $request->feedback;
        $trail->updated_by_id = Auth::user()->id;
        $trail->status_id = 5;

        $report->save();
        $report->trails()->save($trail);

        try{

        } catch(Exception $e){}

        return redirect()->back();
    }

    public function notifySingle($user, $notification){
        if(filled($user)){
            try{
                $user->notify($notification);
            } catch(Exception $e){

            }
        }
    }

    public function notifyGroup($users, $notification){
        if(filled($users)){
            try{
                Notification::send($users, $notification );
            } catch(Exception $e){

            }
        }
    }

}
