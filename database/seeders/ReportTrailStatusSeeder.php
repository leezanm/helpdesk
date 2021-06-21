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
    }
}
