<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportTrail;
use App\Models\User;
use App\Notifications\ReportDoneAdminNotification;
use App\Notifications\ReportDoneReporterNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function show(Report $report)
    {
        //
        $report->submitted_at = convertDateFormat($report->submitted_at, 'LL, h:mm A');
        $report->setAttribute('report_category', $report->category->desc);
        $report->setAttribute('ticket_no', '<a href="'.route('aduan.show', $report).'">'.$report->ticket_no.'</a>');

        $datas = [
            'success' => true,
            'report' => $report,
            'pengadu' => $report->reportBy
        ];
        return response()->json($datas);
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

    public function updateFeedback(Request $request,Report $report){

        $report->report_status_id = 5;
        $report->completed_at = now();
        $report->rating = $request->rating;
        $report->feedback = $request->feedback;
        $user = User::findOrFail($request->user_id);

        $trail = new ReportTrail();
        $trail->message = 'Aduan telah ditanda selesai dengan <br> <b>Rating</b> <br> '. $request->rating. ' <br> <b>Komen</b> <br> '. $request->feedback;
        $trail->updated_by_id = $request->user_id;
        $trail->status_id = 5;

        $report->save();
        $report->trails()->save($trail);


        $report_sent_notification = new ReportDoneReporterNotification($report);
        $this->notifySingle($user, $report_sent_notification);

        $report_received_admin_notification = new ReportDoneAdminNotification($report);
        $users = User::permission('aduan.adminTask')->get();
        $this->notifyGroup($users, $report_received_admin_notification);

        $datas = [
            'success' =>true
        ];
        return response()->json($datas);
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
