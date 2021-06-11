<?php

namespace Database\Seeders;

use App\Models\Ref\ReportStatus;
use Illuminate\Database\Seeder;

class ReportStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $report_status = new ReportStatus();
        $report_status->desc = 'Draf';
        $report_status->class = 'badge bg-secondary';
        $report_status->save();

        $report_status = new ReportStatus();
        $report_status->desc = 'Dihantar';
        $report_status->class = 'badge bg-info';
        $report_status->save();

        $report_status = new ReportStatus();
        $report_status->desc = 'Dihantar (Ambil Maklum)';
        $report_status->class = 'badge bg-info';
        $report_status->save();

        $report_status = new ReportStatus();
        $report_status->desc = 'Sedang Disemak';
        $report_status->class = 'badge bg-warning';
        $report_status->save();

        $report_status = new ReportStatus();
        $report_status->desc = 'Selesai';
        $report_status->class = 'badge bg-success';
        $report_status->save();

        $report_status = new ReportStatus();
        $report_status->desc = 'Ditutup';
        $report_status->class = 'badge bg-danger';
        $report_status->save();
    }
}
