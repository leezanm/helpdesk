<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cuti umum yang ditandakan pada jadual bulanan. Satu rekod untuk semua
 * pegawai; `movement_unit_id` null bermaksud terpakai kepada semua unit.
 */
class MovementHoliday extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function unit()
    {
        return $this->belongsTo(MovementUnit::class, 'movement_unit_id');
    }

    /**
     * Cuti yang bertindih dengan satu tempoh, untuk unit berkenaan atau global.
     * whereDate atas sebab yang sama seperti Movement::scopeOverlapping.
     */
    public function scopeOverlapping($query, $from, $to)
    {
        return $query->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from);
    }

    public function scopeForUnit($query, ?int $unitId)
    {
        return $query->where(function ($q) use ($unitId) {
            $q->whereNull('movement_unit_id');

            if ($unitId !== null) {
                $q->orWhere('movement_unit_id', $unitId);
            }
        });
    }
}
