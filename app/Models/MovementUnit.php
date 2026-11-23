<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit yang mempunyai jadual pergerakan sendiri (contoh: Unit Operasi dan
 * Bantuan Teknikal, BPM).
 */
class MovementUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Semua pegawai dalam roster unit ini, aktif dan tidak aktif. */
    public function officers()
    {
        return $this->hasMany(MonitoredOfficer::class);
    }

    /** Baris jadual bulanan: pegawai aktif sahaja, ikut susunan paparan. */
    public function activeOfficers()
    {
        return $this->officers()->active()->ordered();
    }

    public function holidays()
    {
        return $this->hasMany(MovementHoliday::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
