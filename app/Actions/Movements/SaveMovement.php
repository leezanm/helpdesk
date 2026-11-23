<?php

namespace App\Actions\Movements;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\Ref\MovementType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Cipta atau kemas kini satu entri pergerakan.
 *
 * Kebenaran dikuatkuasakan DI SINI dan bukan hanya pada borang: tindakan
 * Livewire boleh dipanggil terus, jadi semakan pada peringkat simpan ialah
 * lapisan yang sebenarnya melindungi data (corak sama seperti
 * App\Livewire\Aduan\ReportForm yang menyemak dua kali).
 *
 * Pegawai pada satu entri tidak boleh ditukar selepas entri dicipta — menukar
 * pemilik bermakna entri itu berpindah tangan dan boleh memintas peraturan
 * kunci. Hapus entri dan catat yang baharu jika perlu.
 */
class SaveMovement
{
    /**
     * @param  array<string,mixed>  $data  monitored_officer_id, movement_type_id,
     *                                     title, start_date, end_date, start_time,
     *                                     end_time, location, remarks
     */
    public function handle(array $data, ?Movement $movement = null, ?User $user = null): Movement
    {
        $user ??= Auth::user();

        $this->assertDateRange($data);

        if ($movement === null) {
            $officer = MonitoredOfficer::findOrFail($data['monitored_officer_id']);

            // Pegawai mesti ahli roster AKTIF, dan pengguna mesti berhak
            // mencatat untuknya (diri sendiri vs pegawai lain).
            Gate::forUser($user)->authorize('createFor', [Movement::class, $officer]);
        } else {
            Gate::forUser($user)->authorize('update', $movement);
        }

        return DB::transaction(function () use ($data, $movement, $user) {
            $attributes = [
                'movement_type_id' => $data['movement_type_id'],
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'start_time' => $data['start_time'] ?: null,
                'end_time' => $data['end_time'] ?: null,
                'location' => $data['location'] ?: null,
                // Cuti tiada lokasi bertugas: ia tidak boleh dikira sebagai
                // tugas luar pejabat walau apa pun yang dihantar.
                'is_outside' => $this->isLeave($data['movement_type_id'])
                    ? false
                    : (bool) ($data['is_outside'] ?? false),
                'remarks' => $data['remarks'] ?: null,
            ];

            if ($movement === null) {
                return Movement::create($attributes + [
                    'monitored_officer_id' => $data['monitored_officer_id'],
                    'created_by' => $user?->id,
                ]);
            }

            $movement->update($attributes + ['updated_by' => $user?->id]);

            return $movement;
        });
    }

    private function isLeave(int|string|null $typeId): bool
    {
        return (bool) MovementType::whereKey($typeId)->value('is_leave');
    }

    /**
     * Jaring keselamatan untuk invarian julat. Mesej per-medan dipaparkan oleh
     * peraturan pengesahan komponen; ini menghalang data tidak sah masuk
     * melalui mana-mana pemanggil lain.
     */
    private function assertDateRange(array $data): void
    {
        if ($data['end_date'] < $data['start_date']) {
            throw ValidationException::withMessages([
                'endDate' => 'Tarikh tamat tidak boleh lebih awal daripada tarikh mula.',
            ]);
        }

        $start = $data['start_time'] ?? null;
        $end = $data['end_time'] ?? null;

        if ($start && $end && $data['start_date'] === $data['end_date'] && $end <= $start) {
            throw ValidationException::withMessages([
                'endTime' => 'Masa tamat mesti selepas masa mula.',
            ]);
        }
    }
}
