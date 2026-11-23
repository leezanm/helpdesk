<?php

namespace Database\Seeders;

use App\Models\Ref\ReportTrailStatus;
use Illuminate\Database\Seeder;

class ReportTrailStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $status = new ReportTrailStatus();
        $status->desc = 'Dicipta';
        $status->class = 'badge bg-secondary';
        $status->save();

        $status = new ReportTrailStatus();
        $status->desc = 'Dihantar';
        $status->class = 'badge bg-info';
        $status->save();

        $status = new ReportTrailStatus();
        $status->desc = 'Dihantar (Ambil Maklum)';
        $status->class = 'badge bg-primary';
        $status->save();

        $status = new ReportTrailStatus();
        $status->desc = 'Sedang Disemak';
        $status->class = 'badge bg-warning';
        $status->save();

        $status = new ReportTrailStatus();
        $status->desc = 'Selesai';
        $status->class = 'badge bg-success';
        $status->save();

        $status = new ReportTrailStatus();
        $status->desc = 'Ditugaskan Semula';
        $status->class = 'badge bg-warning';
        $status->save();

        $status = new ReportTrailStatus();
        $status->desc = 'KIV Mula';
        $status->class = 'badge bg-warning';
        $status->save();

        $status = new ReportTrailStatus();
        $status->desc = 'KIV Tamat';
        $status->class = 'badge bg-warning';
        $status->save();

        // 9 — peg. bertanggungjawab selesai tindakan (+ lesson learn)
        $status = new ReportTrailStatus();
        $status->desc = 'Selesai Tindakan';
        $status->class = 'badge bg-success';
        $status->save();

        // 10 — peg. bertanggungjawab tidak dapat selesaikan
        $status = new ReportTrailStatus();
        $status->desc = 'Tindakan Lanjut Diperlukan';
        $status->class = 'badge bg-danger';
        $status->save();

        // 11 — peg. helpdesk menetapkan / menukar tahap risiko aduan
        $status = new ReportTrailStatus();
        $status->desc = 'Tahap Risiko Ditetapkan';
        $status->class = 'badge bg-dark';
        $status->save();
    }
}
