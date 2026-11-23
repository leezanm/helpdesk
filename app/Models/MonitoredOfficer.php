<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Seorang pegawai dalam senarai pemantauan (roster) satu unit.
 *
 * `display_name` ialah snapshot nama bergelar ("En. ..." / "Pn. ...") supaya
 * jadual lepas kekal betul walaupun pengguna bertukar nama atau dipadam
 * (soft delete) daripada sistem.
 */
class MonitoredOfficer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function unit()
    {
        return $this->belongsTo(MovementUnit::class, 'movement_unit_id');
    }

    /**
     * Akaun sistem pegawai.
     *
     * Model User tidak menggunakan SoftDeletes (walaupun lajur itu ada), jadi
     * hubungan ini biasa. Jadual tidak bergantung padanya untuk nama — itulah
     * sebab `display_name` disimpan.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Susunan paparan jadual (lajur BIL). */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('display_name');
    }

    /** Pegawai ini mewakili pengguna yang sedang log masuk? */
    public function belongsToUser(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }
}
