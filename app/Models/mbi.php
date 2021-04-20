<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class mbi extends Model
{
    use HasFactory, SoftDeletes;

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
