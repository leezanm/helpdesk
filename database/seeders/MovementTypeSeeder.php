<?php

namespace Database\Seeders;

use App\Models\Ref\MovementType;
use Illuminate\Database\Seeder;

class MovementTypeSeeder extends Seeder
{
    /**
     * Jenis pergerakan asas. Senarai ini berasal daripada kandungan sebenar
     * jadual Google Sheet unit (mesyuarat, kerja luar, bengkel, kursus,
     * perbincangan, cuti rehat, temujanji) dan boleh diselenggara dari UI.
     *
     * Idempoten (firstOrCreate pada `name`) supaya selamat dijalankan semula.
     */
    public function run(): void
    {
        foreach (self::types() as $index => $type) {
            MovementType::firstOrCreate(
                ['name' => $type['name']],
                [
                    'class' => $type['class'],
                    'is_leave' => $type['is_leave'],
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }

    /**
     * Data lalai — satu sumber kebenaran untuk seeder dan ujian.
     *
     * Nilai `class` mesti salah satu daripada
     * App\Livewire\Pergerakan\MovementTypeIndex::BADGE_CLASSES, kerana skrin
     * penyelenggaraan hanya menawarkan warna daripada senarai itu.
     *
     * @return array<int, array{name:string, class:string, is_leave:bool}>
     */
    public static function types(): array
    {
        return [
            ['name' => 'Mesyuarat', 'class' => 'bg-primary', 'is_leave' => false],
            ['name' => 'Perbincangan', 'class' => 'bg-info', 'is_leave' => false],
            ['name' => 'Kerja Luar', 'class' => 'bg-warning', 'is_leave' => false],
            ['name' => 'Bengkel / Seminar', 'class' => 'bg-danger', 'is_leave' => false],
            ['name' => 'Kursus / Latihan', 'class' => 'bg-success', 'is_leave' => false],
            ['name' => 'Tugas Rasmi Luar Negara', 'class' => 'bg-dark', 'is_leave' => false],
            ['name' => 'Cuti Rehat', 'class' => 'bg-secondary', 'is_leave' => true],
            ['name' => 'Cuti Sakit', 'class' => 'bg-secondary', 'is_leave' => true],
            ['name' => 'Cuti Tanpa Rekod', 'class' => 'bg-secondary', 'is_leave' => true],
            ['name' => 'Lain-lain', 'class' => 'bg-light text-dark', 'is_leave' => false],
        ];
    }
}
