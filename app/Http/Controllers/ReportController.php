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
use App\Notifications\ReportDoneAdminNotification;
use App\Notifications\ReportDoneReporterNotification;
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
        $reports = Report::where('report_by_id', Auth::user()->id)->orWhere('bp_user_id', Auth::user()->id)->orderBy('submitted_at', 'desc')->get();
        return view('aduan.index', compact('reports'));
    }

    public function indexDihantar()
    {
        //
        $reports = Report::whereIn('report_status_id', [2,3])->orderBy('submitted_at', 'desc')->get();
        return view('aduan.index', compact('reports'));
    }

    public function indexUntukTindakan()
    {
        //
        $reports = Report::where('report_status_id', 4)
                    ->join('report_trails', 'reports.id', 'report_trails.report_id')
                    ->select('reports.*', 'report_trails.id as trail_id', 'assigned_to_id')
                    ->where('report_trails.id', \DB::raw('(select id from report_trails where report_id = reports.id order by id desc limit 1)'))
                    ->where('assigned_to_id', Auth::user()->id)
                    ->orderBy('submitted_at', 'desc')
                    ->get();

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
        // return $request->attachment_names['4f8d661-9c5c-41ac-8668-25cfd209c090'];
        // return (array)json_decode($request->attachment_names)['4f8d661-9c5c-41ac-8668-25cfd209c090'];

        $attachment_uuids = json_decode($request->attachment_uuids,true);
        $attachment_names = json_decode($request->attachment_names,true);
        $attachment_paths = json_decode($request->attachment_paths,true);
        $attachment_types = json_decode($request->attachment_types,true);

        $report = new Report();
        $report->is_bp = $request->is_bp ;
        $report->report_by_id = Auth::user()->id;
        $report->bahagian_pengadu = Auth::user()->ldap_bahagian;
        $report->remark = $request->remark;
        $report->subject = $request->subject;
        $report->report_status_id = $request->report_status_id;
        $report->report_category_id = $request->report_category_id;
        $report->submitted_at = $request->report_status_id == 2 ? now() : null;

        if($report->is_bp){
            $report->fill($request->only('bp_username', 'bp_name', 'bp_bahagian', 'bp_email', 'bp_user_id'));
        }

        $report->save();

        if(filled($attachment_uuids)){
            $report_attachments = [];
            foreach($attachment_uuids as $uuid => $item){

                $report_attachment = new ReportAttachment();
                $report_attachment->name = $attachment_names[$uuid];
                $report_attachment->path = $attachment_paths[$uuid];
                $report_attachment->type = $attachment_types[$uuid];

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

        return response()->json(['success' => true]);
        // return redirect()->route('aduan.index-user', Auth::user()->id);
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
        $attachments = $report->attachments;
        $attachments->append('file_url');

        return view('aduan.edit', compact('report', 'report_categories', 'attachments'));
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
        $attachment_uuids = json_decode($request->attachment_uuids,true);
        $attachment_names = json_decode($request->attachment_names,true);
        $attachment_paths = json_decode($request->attachment_paths,true);
        $attachment_types = json_decode($request->attachment_types,true);

        $report->is_bp = $request->is_bp ;
        $report->report_by_id = Auth::user()->id;
        $report->bahagian_pengadu = Auth::user()->ldap_bahagian;
        $report->remark = $request->remark;
        $report->subject = $request->subject;
        $report->report_status_id = $request->report_status_id;
        $report->report_category_id = $request->report_category_id;
        $report->submitted_at = $request->report_status_id == 2 ? now() : null;

        if($report->is_bp){
            $report->fill($request->only('bp_username', 'bp_name', 'bp_bahagian', 'bp_email', 'bp_user_id'));
        }

        $report->save();

        if(filled($attachment_uuids)){
            $report_attachments = [];
            foreach($attachment_uuids as $uuid => $item){

                $report_attachment = new ReportAttachment();
                $report_attachment->name = $attachment_names[$uuid];
                $report_attachment->path = $attachment_paths[$uuid];
                $report_attachment->type = $attachment_types[$uuid];

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

        return response()->json(['success' => true]);
        // return redirect()->route('aduan.index-user', Auth::user()->id);

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
        $role = Role::where('name', $request->peranan)->first();
        $report->report_status_id = 4;

        $trail = new ReportTrail();
        $trail->message = 'Aduan telah dihantar kepada <a class="userProfile" data-userId="'. $user->id .'" role="button">'. $user->name . '</a> untuk disemak';
        $trail->assigned_to_id = $request->assigned_to_id;
        $trail->assigned_to_role_id = $role->id;
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


        $report_sent_notification = new ReportDoneReporterNotification($report);
        $this->notifySingle(Auth::user(), $report_sent_notification);

        $report_received_admin_notification = new ReportDoneAdminNotification($report);
        $users = User::permission('aduan.assignTask')->get();
        $this->notifyGroup($users, $report_received_admin_notification);


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
