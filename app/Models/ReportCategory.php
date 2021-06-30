<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCategory extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function getImageAttribute(){
        return asset($this->image_path);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
