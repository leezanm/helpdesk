<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    public function report(){
        return $this->belongsTo(Report::class);
    }

    public function assignedBy(){
        return $this->belongsTo(User::class);
    }

    public function assignedTo(){
        return $this->belongsTo(User::class);
    }

    public function taskStatus(){
        return $this->belongsTo(Ref\TaskStatus::class);
    }
}
