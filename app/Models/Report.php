<?php

namespace App\Models;

use App\Models\Ref\ReportStatus;
use App\Observers\ReportObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Report extends Model
{
    use HasFactory;

    protected $fillable = ['bp_username', 'bp_name', 'bp_bahagian', 'bp_email', 'bp_user_id'];

    public function category(){
        return $this->belongsTo(ReportCategory::class, 'report_category_id');
    }

    public function status(){
        return $this->belongsTo(ReportStatus::class, 'report_status_id');
    }

    /*
    *pengadu
    */
    public function reportBy(){
        return $this->belongsTo(User::class);
    }

    /*
    bagi pihak user
    */
    public function bpUser(){
        return $this->belongsTo(User::class);
    }

    public function attachments(){
        return $this->hasMany(ReportAttachment::class);
    }

    public function trails(){
        return $this->hasMany(ReportTrail::class);
    }

    public function currentTrail(){
        return $this->hasOne(ReportTrail::class)->latest();
    }

    // protected static function booted()
    // {
    //     Report::observe(ReportObserver::class);
    // }
}
