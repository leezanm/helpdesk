<?php

namespace Database\Seeders;

use App\Models\Ref\RiskLevel;
use Illuminate\Database\Seeder;

class RiskLevelSeeder extends Seeder
{
    /**
     * Risk levels (tahap risiko) for an aduan, set by the helpdesk officer.
     * `level` is the stable key (1..5) referenced by code and reports; `desc`
     * and `reference` are editable from the Selenggaraan screen.
     *
     * Idempotent (firstOrCreate on `level`) so it is safe to re-run and safe
     * to call from a migration.
     */
    public function run(): void
    {
        foreach (self::levels() as $level) {
            RiskLevel::firstOrCreate(
                ['level' => $level['level']],
                [
                    'desc' => $level['desc'],
                    'reference' => $level['reference'],
                    'class' => $level['class'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Canonical default data — single source of truth for the seeder and for
     * the migration that backfills existing aduan.
     *
     * @return array<int, array{level:int, desc:string, reference:string, class:string}>
     */
    public static function levels(): array
    {
        return [
            [
                'level' => 1,
                'desc' => 'Sangat Rendah',
                'class' => 'badge bg-secondary',
                'reference' => 'Aduan yang mempunyai impak minimum terhadap pengguna dan tidak menjejaskan keselamatan maklumat atau operasi perkhidmatan ICT secara signifikan. Masalah boleh diselesaikan melalui tindakan rutin tanpa memerlukan eskalasi khas.',
            ],
            [
                'level' => 2,
                'desc' => 'Rendah',
                'class' => 'badge bg-info',
                'reference' => 'Aduan yang melibatkan gangguan kecil kepada pengguna atau peralatan ICT, tetapi tidak menjejaskan operasi jabatan secara ketara. Risiko keselamatan boleh dikawal melalui tindakan pembetulan biasa.',
            ],
            [
                'level' => 3,
                'desc' => 'Sederhana',
                'class' => 'badge bg-primary',
                'reference' => 'Aduan yang berpotensi menjejaskan produktiviti, ketersediaan sistem atau keselamatan maklumat sekiranya tidak ditangani. Memerlukan penilaian lanjut, tindakan mitigasi dan pemantauan oleh pegawai bertanggungjawab.',
            ],
            [
                'level' => 4,
                'desc' => 'Tinggi',
                'class' => 'badge bg-warning',
                'reference' => 'Aduan atau insiden yang berpotensi menjejaskan kerahsiaan, integriti atau ketersediaan maklumat, melibatkan beberapa pengguna atau mengganggu perkhidmatan penting. Memerlukan tindakan segera dan eskalasi kepada pegawai teknikal atau pegawai keselamatan yang berkaitan.',
            ],
            [
                'level' => 5,
                'desc' => 'Sangat Tinggi',
                'class' => 'badge bg-danger',
                'reference' => 'Aduan atau insiden yang menunjukkan kemungkinan ancaman serius terhadap keselamatan siber, kebocoran maklumat sensitif, kompromi akaun pentadbir, serangan ransomware atau gangguan perkhidmatan kritikal. Memerlukan eskalasi serta-merta, tindakan pembendungan yang bersesuaian dan pengaktifan prosedur tindak balas insiden jika berkenaan.',
            ],
        ];
    }
}
