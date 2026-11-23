<?php

namespace App\Models;

use App\Models\Ref\MovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu entri pergerakan: SATU aktiviti dengan julat tarikh.
 *
 * Aktiviti berbilang hari ialah satu rekod (bukan satu rekod per hari); grid
 * bulanan yang menebarkannya ke hari-hari dalam julat.
 */
class Movement extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_outside' => 'boolean',
    ];

    public function officer()
    {
        return $this->belongsTo(MonitoredOfficer::class, 'monitored_officer_id');
    }

    public function type()
    {
        return $this->belongsTo(MovementType::class, 'movement_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Entri yang julatnya bertindih dengan tempoh yang diberi.
     *
     * whereDate, bukan where: pada MySQL lajur ini DATE, tetapi pada SQLite
     * (yang digunakan oleh ujian) nilainya disimpan sebagai "Y-m-d 00:00:00".
     * Perbandingan rentetan biasa akan tersilap pada hari yang sama —
     * '2026-11-20 00:00:00' <= '2026-11-20' adalah palsu. whereDate
     * membandingkan bahagian tarikh sahaja, jadi ia betul pada kedua-duanya.
     */
    public function scopeOverlapping($query, $from, $to)
    {
        return $query->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from);
    }

    public function scopeForUnit($query, int $unitId)
    {
        return $query->whereHas('officer', fn ($q) => $q->where('movement_unit_id', $unitId));
    }

    public function isMultiDay(): bool
    {
        return ! $this->start_date->isSameDay($this->end_date);
    }

    /**
     * Entri terkunci sebaik tarikh mula sampai — hari ini pun sudah terkunci.
     * Selepas ini hanya pemegang `pergerakan.manageLocked` boleh mengubahnya.
     * Entri berbilang hari yang sudah bermula turut terkunci kerana
     * `start_date` sudah lepas.
     */
    public function isLocked(): bool
    {
        return $this->start_date->startOfDay()->lessThanOrEqualTo(now()->startOfDay());
    }
}
