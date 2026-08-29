<?php

namespace Database\Seeders;

use App\Models\Ref\ReportStatus;
use Illuminate\Database\Seeder;

class ReportStatusSeeder extends Seeder
{
    /**
     * Report statuses for the aduan flow (see flow baru.md).
     * IDs are significant and referenced throughout the code:
     *   1 Draf, 2 Baru, 3 Aduan Disemak, 4 Dalam Tindakan, 5 Selesai,
     *   6 KIV, 7 Selesai Tindakan, 8 Tindakan Lanjut Diperlukan.
     */
    public function run()
    {
        $statuses = [
            ['desc' => 'Draf', 'reference' => 'Aduan belum dihantar', 'class' => 'badge bg-secondary'],
            ['desc' => 'Baru', 'reference' => 'Aduan baru dihantar oleh pengadu', 'class' => 'badge bg-info'],
            ['desc' => 'Aduan Disemak', 'reference' => 'Aduan telah disemak oleh peg. helpdesk', 'class' => 'badge bg-primary'],
            ['desc' => 'Dalam Tindakan', 'reference' => 'Aduan dimajukan kepada peg. teknikal', 'class' => 'badge bg-warning'],
            ['desc' => 'Selesai', 'reference' => 'Aduan ditanda selesai', 'class' => 'badge bg-success'],
            ['desc' => 'KIV', 'reference' => 'Aduan di KIV', 'class' => 'badge bg-warning'],
            ['desc' => 'Selesai Tindakan', 'reference' => 'Peg. bertanggungjawab telah membuat tindakan', 'class' => 'badge bg-success'],
            ['desc' => 'Tindakan Lanjut Diperlukan', 'reference' => 'Aduan tidak dapat diselesaikan, perlu tindakan lanjut', 'class' => 'badge bg-danger'],
        ];

        foreach ($statuses as $status) {
            $report_status = new ReportStatus();
            $report_status->desc = $status['desc'];
            $report_status->reference = $status['reference'];
            $report_status->class = $status['class'];
            $report_status->save();
        }
    }
}
