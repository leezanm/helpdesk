<?php

namespace App\Models\Ref;

use App\Models\Movement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Jenis pergerakan — data rujukan yang boleh diselenggara dari UI
 * (Mesyuarat, Kerja Luar, Bengkel, Kursus/Latihan, Cuti Rehat, ...).
 *
 * `is_leave` menandakan jenis cuti. Ia digunakan untuk memberi amaran apabila
 * satu aktiviti bertindih dengan cuti pegawai yang sama — amaran, bukan halangan.
 */
class MovementType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = '_movement_types';
    protected $guarded = ['id'];

    protected $casts = [
        'is_leave' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function movements()
    {
        return $this->hasMany(Movement::class, 'movement_type_id');
    }

    /** Hanya jenis aktif boleh dipilih untuk entri baharu. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
