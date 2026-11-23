<?php

namespace App\Models\Ref;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Risk level (tahap risiko) of an aduan — seeded reference data, levels 1..5.
 * The helpdesk officer assigns one to every aduan before it can be forwarded
 * to a technical officer.
 */
class RiskLevel extends Model
{
    use HasFactory;

    protected $table = '_risk_levels';
    protected $guarded = ['id'];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class, 'risk_level_id');
    }

    /** Only active levels may be picked on a new assessment. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** "Tahap 3: Sederhana" — used in dropdowns and badges. */
    public function getLabelAttribute(): string
    {
        return 'Tahap '.$this->level.': '.$this->desc;
    }
}
