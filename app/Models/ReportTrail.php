<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class ReportTrail extends Model
{
    use HasFactory;

    public function status(){
        return $this->belongsTo(Ref\ReportTrailStatus::class);
    }

    public function assignedTo(){
        return $this->belongsTo(User::class);
    }

    public function assignedToRole(){
        return $this->belongsTo(Role::class);
    }

    public function updatedBy(){
        return $this->belongsTo(User::class);
    }

    public function report(){
        return $this->belongsTo(Report::class);
    }
}
