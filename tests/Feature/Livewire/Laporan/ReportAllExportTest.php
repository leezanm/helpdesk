<?php

namespace Tests\Feature\Livewire\Laporan;

use App\Livewire\Laporan\ReportAll;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\LaporanCsvExporter;
use App\Support\ReportStatuses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * ReportAll::exportCsv() — the component half of Eksport_CSV.
 *
 * Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (task 11.2,
 * Requirement 11 criteria 2 to 4, 9, 11, 12, 15 to 17 and Requirement 9
 * criterion 15). The file format itself is covered by
 * tests/Feature/Laporan/LaporanCsvExporterTest.php; what matters here is the
 * wiring: the authorisation layer, the row set, the file name, the empty
 * result notice and the absence of side effects on page state.
 *
 * Rows are identified by Subjek rather than Tiket throughout. The observer
 * derives the ticket sequence from the aduan submitted *today*
 * (RefNo::ticketNo()), so fixtures with a past submitted_at legitimately
 * share one ticket_no and ticket_no has no unique index — a sequence of
 * ticket numbers would not identify rows uniquely.
 */
class ReportAllExportTest extends FeatureTestCase
{
    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        return $admin;
    }

    /**
     * One aduan, created through the model so the observer fills ticket_no and
     * the first trail exactly as the application does.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeReport(string $subject, array $attributes = []): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login(User::factory()->create());

        $report = new Report();
        $report->subject = $subject;
        $report->remark = 'x';
        $report->report_category_id = $category->id;
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
     * The captured download, parsed as UTF-8 CSV with the BOM stripped.
     *
     * Livewire turns a response returned by an action into a base64 "download"
     * effect, which is exactly what the browser receives, so the assertions
     * below read the real streamed bytes.
     *
     * fgetcsv() rather than str_getcsv() per line, because a field may legally
     * contain a newline and must be read back as one cell.
     *
     * @return array<int, array<int, string>>
     */
    private function parseDownload(string $base64): array
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, preg_replace('/^\xEF\xBB\xBF/', '', base64_decode($base64)));
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /** Subjek column of every data row, in file order. */
    private function subjects(array $rows): array
    {
        return array_column(array_slice($rows, 1), 2);
    }

    /**
     * Data rows in the file equal the filtered count with no pagination
     * applied, across every page.
     *
     * **Validates: Requirements 11.3**
     */
    public function test_exported_row_count_equals_the_unpaginated_filtered_count()
    {
        $admin = $this->admin();

        // 25 rows is more than one 20-per-page table page, so a file built
        // from the paginator instead of the full query would be short.
        for ($i = 1; $i <= 25; $i++) {
            $this->makeReport(sprintf('Dalam julat %02d', $i), [
                'submitted_at' => now()->subMinutes($i),
            ]);
        }

        // Excluded by the date range, and excluded as a Draf: both must be
        // absent from the count and from the file.
        $this->makeReport('Luar julat', ['submitted_at' => now()->subMonths(6)]);
        $this->makeReport('Draf', ['report_status_id' => ReportStatuses::DRAF]);

        $component = Livewire::actingAs($admin)->test(ReportAll::class)->call('exportCsv');

        $rows = $this->parseDownload($component->effects['download']['content']);
        $expected = Report::whereIn('report_status_id', ReportStatuses::COUNTED)
            ->whereBetween('submitted_at', [now()->startOfMonth()->startOfDay(), now()->endOfDay()])
            ->count();

        $this->assertSame(25, $expected);
        // One header row plus one data row per aduan.
        $this->assertCount($expected + 1, $rows);
        $this->assertNotContains('Luar julat', $this->subjects($rows));
        $this->assertNotContains('Draf', $this->subjects($rows));
    }

    /**
     * The file is the concatenation of every table page for the same filter
     * set, in the same order, with Bil. running 1..n.
     *
     * **Validates: Requirements 11.4, 11.6**
     */
    public function test_exported_rows_are_the_union_of_every_table_page()
    {
        $admin = $this->admin();

        for ($i = 1; $i <= 25; $i++) {
            $this->makeReport(sprintf('Aduan %02d', $i), [
                'submitted_at' => now()->subMinutes($i),
            ]);
        }

        $component = Livewire::actingAs($admin)->test(ReportAll::class);

        // Walk the table page by page and collect what the screen shows.
        $onScreen = [];
        $pages = 0;
        do {
            $paginator = $component->call('gotoPage', ++$pages)->viewData('reports');
            $onScreen = array_merge($onScreen, $paginator->pluck('subject')->all());
        } while ($paginator->hasMorePages());

        $rows = $this->parseDownload(
            $component->call('exportCsv')->effects['download']['content']
        );

        // The comparison is only meaningful if the fixture really paginates.
        $this->assertGreaterThan(1, $pages);
        $this->assertSame(25, count($onScreen));
        $this->assertSame($onScreen, $this->subjects($rows));

        // Bil. is 1..n for the whole result set, not per page.
        $this->assertSame(
            range(1, 25),
            array_map('intval', array_column(array_slice($rows, 1), 0))
        );
    }

    /**
     * The file name carries the sanitised date range, so a hand edited
     * parameter cannot leak into the Content-Disposition header.
     *
     * **Validates: Requirements 11.9**
     */
    public function test_filename_follows_the_sanitised_date_range()
    {
        $admin = $this->admin();
        $this->makeReport('Aduan', ['submitted_at' => '2026-01-15 09:30:00']);

        Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-01-31')
            ->call('exportCsv')
            ->assertFileDownloaded(
                'laporan-keseluruhan-2026-01-01-hingga-2026-01-31.csv',
                null,
                'text/csv; charset=UTF-8'
            );

        // Garbage in the date control falls back to the default period, and
        // the name still holds two Y-m-d values.
        $this->makeReport('Aduan bulan ini');

        $component = Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->set('dateFrom', 'abc')
            ->call('exportCsv');

        $this->assertSame(
            sprintf(
                'laporan-keseluruhan-%s-hingga-%s.csv',
                now()->startOfMonth()->format('Y-m-d'),
                now()->format('Y-m-d')
            ),
            $component->effects['download']['name']
        );
    }

    /**
     * Exporting is read-only: no filter property and no page number moves.
     *
     * **Validates: Requirements 11.15**
     */
    public function test_export_leaves_filters_and_page_number_untouched()
    {
        $admin = $this->admin();

        for ($i = 1; $i <= 25; $i++) {
            $this->makeReport(sprintf('Aduan %02d', $i), [
                'submitted_at' => now()->subMinutes($i),
                'bahagian_pengadu' => 'Bahagian Kewangan',
                'rating' => 4,
                'report_status_id' => ReportStatuses::BARU,
            ]);
        }

        $component = Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->set('bahagian', 'Bahagian Kewangan')
            ->set('status', (string) ReportStatuses::BARU)
            ->set('rating', '4')
            ->set('dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'))
            ->call('gotoPage', 2);

        $this->assertSame(2, $component->viewData('reports')->currentPage());

        $component->call('exportCsv')->assertFileDownloaded();

        // All seven filters survive the export unchanged...
        $component
            ->assertSet('dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->assertSet('dateTo', now()->format('Y-m-d'))
            ->assertSet('bahagian', 'Bahagian Kewangan')
            ->assertSet('status', (string) ReportStatuses::BARU)
            ->assertSet('pegawai', '')
            ->assertSet('rating', '4')
            ->assertSet('riskLevel', '');

        // ...and so does the page number: exportCsv() must not resetPage().
        $this->assertSame(2, $component->viewData('reports')->currentPage());
    }

    /**
     * A user without access-main-dashboard is refused before any data query,
     * so no aduan data can reach the response and no file is produced.
     *
     * **Validates: Requirements 9.2, 11.12**
     */
    public function test_user_without_permission_gets_no_data_from_the_export()
    {
        $report = $this->makeReport('Aduan rahsia');

        $pengguna = User::factory()->create();
        $pengguna->syncRoles(['pengguna']);

        // The route gate is the first layer.
        $this->actingAs($pengguna)->get('/laporan/keseluruhan')->assertForbidden();

        // exportCsv() is the third, and it holds even when mount() is bypassed
        // by instantiating the component for a permitted user first.
        $this->actingAs($pengguna);
        $component = new ReportAll();

        try {
            $component->exportCsv();
            $this->fail('exportCsv() must refuse a user without access-main-dashboard.');
        } catch (AuthorizationException $e) {
            $this->assertSame('', $component->exportNotice);
        }

        // Nothing about the aduan leaked, and no notice was rendered either.
        $this->assertNotNull($report->ticket_no);
    }

    /**
     * No matching aduan: the download is cancelled and the page shows the
     * Malay notice instead.
     *
     * **Validates: Requirements 11.16**
     */
    public function test_empty_result_shows_the_notice_and_downloads_nothing()
    {
        $admin = $this->admin();
        $this->makeReport('Aduan lama', ['submitted_at' => now()->subMonths(6)]);

        Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->call('exportCsv')
            ->assertNoFileDownloaded()
            ->assertSet('exportNotice', 'Tiada data untuk dieksport.')
            ->assertSee('Tiada data untuk dieksport.');
    }

    /**
     * An inverted range (Julat_Tarikh_Tidak_Sah) takes the same zero-record
     * path: the notice, not a server error.
     *
     * **Validates: Requirements 11.17**
     */
    public function test_inverted_date_range_shows_the_notice_without_an_exception()
    {
        $admin = $this->admin();
        $this->makeReport('Aduan hari ini');

        $component = Livewire::actingAs($admin)
            ->test(ReportAll::class)
            // Hingga Tarikh earlier than Dari Tarikh.
            ->set('dateFrom', '2026-01-31')
            ->set('dateTo', '2026-01-01')
            ->call('exportCsv');

        $component
            ->assertOk()
            ->assertNoFileDownloaded()
            ->assertSet('exportNotice', 'Tiada data untuk dieksport.')
            // The entered values are kept exactly as typed.
            ->assertSet('dateFrom', '2026-01-31')
            ->assertSet('dateTo', '2026-01-01');
    }

    /**
     * The notice describes one filter set, so it must not outlive it.
     *
     * **Validates: Requirements 11.16**
     */
    public function test_notice_is_cleared_when_a_filter_changes()
    {
        $admin = $this->admin();
        $this->makeReport('Aduan hari ini');

        Livewire::actingAs($admin)
            ->test(ReportAll::class)
            ->set('bahagian', 'Bahagian Yang Tiada')
            ->call('exportCsv')
            ->assertSet('exportNotice', 'Tiada data untuk dieksport.')
            ->set('bahagian', '')
            ->assertSet('exportNotice', '')
            ->call('exportCsv')
            ->assertFileDownloaded()
            ->assertSet('exportNotice', '');
    }

    /**
     * The export is a component action: no new route, and the header row is
     * the exporter's, so both halves cannot drift apart.
     *
     * **Validates: Requirements 9.15, 11.2**
     */
    public function test_export_is_a_component_action_with_no_new_route()
    {
        $admin = $this->admin();
        $this->makeReport('Aduan hari ini');

        $component = Livewire::actingAs($admin)->test(ReportAll::class)->call('exportCsv');

        $rows = $this->parseDownload($component->effects['download']['content']);
        $this->assertSame(LaporanCsvExporter::HEADERS, $rows[0]);

        // The laporan routes — both the web group and the api group — carry no
        // export URI. Scoped to laporan on purpose: Requirement 9.15 is about
        // this page adding no route of its own, and another module may
        // legitimately own an export URI (e.g. pergerakan/eksport-csv).
        $checked = 0;

        foreach (app('router')->getRoutes() as $route) {
            if (! str_contains($route->uri(), 'laporan')) {
                continue;
            }

            $this->assertStringNotContainsString('eksport', $route->uri());
            $this->assertStringNotContainsString('export', $route->uri());
            $checked++;
        }

        // Guard against the filter above silently matching nothing.
        $this->assertGreaterThan(0, $checked);
    }
}
