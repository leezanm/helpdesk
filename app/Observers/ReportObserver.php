<?php

namespace App\Observers;

use App\Helpers\RefNo;
use App\Models\Report;
use App\Models\ReportTrail;
use Illuminate\Support\Facades\Auth;

class ReportObserver
{
    public function creating(Report $report)
    {
        // Default the owner to the logged-in user, but DO NOT override an
        // explicitly-set owner. For "bagi pihak" aduan, SaveReport sets
        // report_by_id to the represented person so the aduan is recorded
        // under them, not the acting wakil.
        if (blank($report->report_by_id)) {
            $report->report_by_id = Auth::user()->id;
        }
    }

    public function saving(Report $report)
    {
        //
        if($report->report_status_id == 2 && blank($report->ticket_no)){
            $report->ticket_no = RefNo::ticketNo();
        }

    }

    /**
     * Handle the Report "created" event.
     *
     * @param  \App\Models\Report  $report
     * @return void
     */
    public function created(Report $report)
    {
        //
        $report_status_id = $report->report_status_id;
        switch($report_status_id){
            case 1:
                $message = 'Aduan dicipta';
                $status_id = 1;
                break;
            case 2:
                $message = 'Aduan dihantar';
                $status_id = 2;
                break;
            default:
                // Reports are only ever created as Draf (1) or Dihantar (2);
                // any other initial status has no first-trail message.
                return;
        }

        $trail = new ReportTrail();
        $trail->message = $message;
        $trail->updated_by_id = Auth::user()->id;
        $trail->status_id = $status_id;

        $report->trails()->save($trail);
    }

    /**
     * Handle the Report "updated" event.
     *
     * @param  \App\Models\Report  $report
     * @return void
     */
    public function updated(Report $report)
    {
        //
    }

    /**
     * Handle the Report "deleted" event.
     *
     * @param  \App\Models\Report  $report
     * @return void
     */
    public function deleted(Report $report)
    {
        //
    }

    /**
     * Handle the Report "restored" event.
     *
     * @param  \App\Models\Report  $report
     * @return void
     */
    public function restored(Report $report)
    {
        //
    }

    /**
     * Handle the Report "force deleted" event.
     *
     * @param  \App\Models\Report  $report
     * @return void
     */
    public function forceDeleted(Report $report)
    {
        //
    }
}
