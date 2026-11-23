<?php

namespace Tests\Feature\Laporan;

use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\LaporanCsvExporter;
use App\Support\ReportStatuses;
use Database\Seeders\RiskLevelSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\FeatureTestCase;

/**
 * LaporanCsvExporter in isolation.
 *
 * Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (task 11.1,
 * Requirement 11 criteria 5 to 8, 10 and 18).
 *
 * The exporter writes whatever query it is handed, so these tests hand it a
 * query with the same shape as ReportAll::listQuery() (same eager loads, same
 * submitted_at + id ordering) and read the bytes back. Wiring the component
 * action to the exporter is task 11.2 and is not exercised here.
 *
 * Ticket numbers are not asserted to be distinct anywhere below: the
 * ReportObserver derives the sequence from the aduan submitted *today*
 * (RefNo::ticketNo()), so fixtures with a past submitted_at legitimately
 * share one ticket_no and ticket_no has no unique index.
 */
class LaporanCsvExporterTest extends FeatureTestCase
{
    private function category(string $desc = 'Rangkaian'): ReportCategory
    {
        return ReportCategory::firstOrCreate(['desc' => $desc], ['is_active' => true]);
    }

    /**
     * One aduan, created through the model so the observer fills ticket_no and
     * the first trail exactly as it does in the application.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeReport(array $attributes = []): Report
    {
        Auth::login(User::factory()->create());

        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $this->category()->id;
        $report->report_status_id = ReportStatuses::BARU;
        $report->submitted_at = now();

        foreach ($attributes as $column => $value) {
            $report->{$column} = $value;
        }

        $report->save();

        Auth::logout();

        return $report->fresh();
    }

    /**
     * The query the component hands over: same eager loads and same total
     * ordering as ReportAll::listQuery(). The orderByDesc('id') tie breaker is
     * what keeps chunk() (offset/limit) from skipping or repeating rows that
     * share a submitted_at value.
     *
     * @return Builder<Report>
     */
    private function listQuery(): Builder
    {
        return Report::query()
            ->whereIn('report_status_id', ReportStatuses::COUNTED)
            ->with(['status', 'category', 'riskLevel', 'reportBy'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');
    }

    /** The streamed body, as the browser would receive it. */
    private function capture(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    /**
     * Parse the captured file as UTF-8 CSV, BOM stripped.
     *
     * fgetcsv() rather than str_getcsv() per line, because a field may legally
     * contain a newline and must be read back as one cell.
     *
     * @return array<int, array<int, string>>
     */
    private function parse(string $csv): array
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    public function test_header_row_is_the_nine_malay_columns_in_order()
    {
        $this->makeReport();

        $rows = $this->parse($this->capture(
            LaporanCsvExporter::stream($this->listQuery(), 'laporan.csv')
        ));

        $this->assertSame(
            ['Bil.', 'Tiket', 'Subjek', 'Bahagian', 'Kategori', 'Risiko', 'Tarikh', 'Status', 'Rating'],
            $rows[0]
        );
        $this->assertSame(LaporanCsvExporter::HEADERS, $rows[0]);
    }

    public function test_file_begins_with_the_utf8_bom()
    {
        $this->makeReport();

        $csv = $this->capture(LaporanCsvExporter::stream($this->listQuery(), 'laporan.csv'));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function test_response_carries_csv_content_type_and_attachment_filename()
    {
        $response = LaporanCsvExporter::stream(
            $this->listQuery(),
            'laporan-keseluruhan-2026-01-01-hingga-2026-01-31.csv'
        );

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment', $disposition);
        $this->assertStringContainsString('laporan-keseluruhan-2026-01-01-hingga-2026-01-31.csv', $disposition);
    }

    public function test_row_carries_the_full_record_with_risk_label_long_date_and_raw_rating()
    {
        $this->seed(RiskLevelSeeder::class);
        $risk = RiskLevel::where('level', 3)->firstOrFail();

        $report = $this->makeReport([
            'submitted_at' => '2026-01-15 09:30:00',
            'bahagian_pengadu' => 'Bahagian Kewangan',
            'risk_level_id' => $risk->id,
            'rating' => 4,
        ]);

        $rows = $this->parse($this->capture(
            LaporanCsvExporter::stream($this->listQuery(), 'laporan.csv')
        ));

        $this->assertCount(2, $rows);
        $this->assertSame([
            '1',
            $report->ticket_no,
            $report->subject,
            'Bahagian Kewangan',
            'Rangkaian',
            'Tahap 3: Sederhana',
            '15 Januari 2026',
            'Baru',
            '4',
        ], $rows[1]);
    }

    public function test_empty_fields_become_exactly_one_dash()
    {
        $report = $this->makeReport([
            'submitted_at' => '2026-01-15 09:30:00',
            'bahagian_pengadu' => null,
            'risk_level_id' => null,
            'rating' => null,
        ]);

        $rows = $this->parse($this->capture(
            LaporanCsvExporter::stream($this->listQuery(), 'laporan.csv')
        ));

        // Bahagian, Risiko and Rating are empty on this aduan.
        $this->assertSame('-', $rows[1][3]);
        $this->assertSame('-', $rows[1][5]);
        $this->assertSame('-', $rows[1][8]);

        // Kategori is a non-nullable foreign key, so an aduan with no category
        // only exists before it is saved. row() is checked directly for it,
        // together with the empty submitted_at and status cases.
        $bare = new Report();
        $bare->subject = 'Tiada rujukan';

        $this->assertSame(
            [7, '-', 'Tiada rujukan', '-', '-', '-', '-', '-', '-'],
            LaporanCsvExporter::row($bare, 7)
        );

        $this->assertNotNull($report->ticket_no);
    }

    public function test_subject_is_exported_in_full_and_survives_a_csv_round_trip()
    {
        // 40 characters is where the on-screen table truncates with
        // Str::limit(); the export must not. The value also carries a comma, a
        // double quote, a newline and Malay characters, all of which must come
        // back unchanged through fputcsv()/fgetcsv().
        $subject = 'Pelayan "utama" tidak berfungsi, pengguna tidak dapat mengakses sistem; '
            ."aduan susulan\nbagi kerosakan pepasangan rangkaian di Bahagian Kewangan — perlu tindakan segera";

        $this->makeReport(['subject' => $subject]);

        $rows = $this->parse($this->capture(
            LaporanCsvExporter::stream($this->listQuery(), 'laporan.csv')
        ));

        $this->assertGreaterThan(40, mb_strlen($subject));
        $this->assertSame($subject, $rows[1][2]);
    }

    public function test_streams_in_batches_of_500_with_one_query_per_batch()
    {
        $total = 1200;
        $owner = User::factory()->create();
        $category = $this->category();

        // Inserted directly: this test is about batching, and 1,200 passes
        // through the observer (a ticket_no lookup and a trail insert each)
        // would dominate the runtime without changing what is being measured.
        $now = now();
        foreach (array_chunk(range(1, $total), 300) as $batch) {
            DB::table('reports')->insert(array_map(fn (int $i) => [
                'ticket_no' => '260115/HQ/001',
                'subject' => 'Aduan '.$i,
                'remark' => 'x',
                'report_by_id' => $owner->id,
                'report_category_id' => $category->id,
                'report_status_id' => ReportStatuses::BARU,
                'submitted_at' => $now->copy()->subMinutes($i)->toDateTimeString(),
                'created_at' => $now,
                'updated_at' => $now,
            ], $batch));
        }

        $batchQueries = 0;
        DB::listen(function ($query) use (&$batchQueries) {
            // The chunk pages are the only queries that read the reports
            // table; the eager loads read the reference tables.
            if (str_contains($query->sql, 'from "reports"')) {
                $batchQueries++;
            }
        });

        $rows = $this->parse($this->capture(
            LaporanCsvExporter::stream($this->listQuery(), 'laporan.csv')
        ));

        $this->assertSame((int) ceil($total / LaporanCsvExporter::CHUNK_SIZE), $batchQueries);
        $this->assertSame(500, LaporanCsvExporter::CHUNK_SIZE);

        // Every record is written once, and Bil. runs 1..n across the batches
        // instead of restarting at each one.
        $this->assertCount($total + 1, $rows);
        $this->assertSame('1', $rows[1][0]);
        $this->assertSame((string) $total, $rows[$total][0]);
        $this->assertSame(
            range(1, $total),
            array_map('intval', array_column(array_slice($rows, 1), 0))
        );
    }
}
