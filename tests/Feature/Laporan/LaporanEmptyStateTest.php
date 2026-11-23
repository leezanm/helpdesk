<?php

namespace Tests\Feature\Laporan;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FeatureTestCase;

/**
 * Keadaan kosong merentas bahagian Laporan Keseluruhan.
 * Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 12.2)
 *
 * Setiap ujian di sini merender halaman sebenar melalui HTTP, kerana apa yang
 * diuji ialah markup yang sampai kepada penyemak imbas: lapan Kad_Statistik
 * bernilai 0, kedua-dua bekas carta membawa keadaan kosong, dan Jadual_Aduan
 * membawa baris keadaan kosong tanpa pautan penomboran.
 *
 * Requirement 4 kriteria 6, Requirement 5 kriteria 9 dan 11, Requirement 6
 * kriteria 14, Requirement 7 kriteria 5, Requirement 8 kriteria 12, Requirement
 * 9 kriteria 12 dan 13.
 */
class LaporanEmptyStateTest extends FeatureTestCase
{
    private const CHART_EMPTY_TEXT = 'Tiada data untuk tempoh ini';

    private const TABLE_EMPTY_TEXT = 'Tiada aduan sepadan dengan penapis.';

    private const CHART_FALLBACK_TEXT = 'Carta tidak dapat dipaparkan.';

    private function managementUser(): User
    {
        $user = User::factory()->create();
        $user->syncRoles(['helpdesk.admin']);

        return $user;
    }

    private function makeReport(int $statusId = 2, ?string $submittedAt = null): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login(User::factory()->create());

        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = 2;
        $report->submitted_at = $submittedAt ?? now();
        $report->save();

        if ($statusId !== 2) {
            $report->report_status_id = $statusId;
            $report->save();
        }

        Auth::logout();

        return $report->fresh();
    }

    /** Nilai setiap kad statistik, mengikut susunan ia dirender. */
    private function statTileValues(string $html): array
    {
        preg_match_all('#<div class="status-tile__value">([^<]*)</div>#', $html, $matches);

        return array_map('trim', $matches[1]);
    }

    /**
     * Isi setiap bekas `.chart-box`, satu segmen per bekas.
     *
     * Dipecahkan pada penanda kelas dan bukan dihurai sebagai DOM, kerana yang
     * diperiksa ialah elemen mana yang berada DI DALAM bekas berketinggian
     * tetap itu — iaitu tepat soalan Requirement 7 kriteria 5.
     */
    private function chartBoxes(string $html): array
    {
        $segments = explode('class="chart-box"', $html);
        array_shift($segments);

        // Potong setiap segmen pada penghujung bekas. `.chart-fallback` ialah
        // elemen terakhir dalam `.chart-box`, jadi `</div>` kedua selepas
        // penanda fallback menutup bekas itu sendiri; tanpa potongan ini
        // segmen terakhir akan menelan keseluruhan halaman yang berbaki.
        return array_map(function (string $segment) {
            $fallback = strpos($segment, 'class="chart-fallback"');

            if ($fallback === false) {
                return $segment;
            }

            $closeFallback = strpos($segment, '</div>', $fallback);
            $closeBox = $closeFallback === false
                ? false
                : strpos($segment, '</div>', $closeFallback + 6);

            return $closeBox === false ? $segment : substr($segment, 0, $closeBox + 6);
        }, $segments);
    }

    /**
     * Pengesahan bersama bagi ketiga-tiga kes kosong (julat terbalik, julat sah
     * tanpa aduan sepadan, dan id penapis yang tidak wujud): kesemuanya mesti
     * menghasilkan paparan yang sama.
     */
    private function assertEverySectionIsEmpty(string $html, string $context): void
    {
        // Lapan Kad_Statistik: tujuh status COUNTED + kad "Jumlah", semuanya 0
        // (Requirement 9 kriteria 12).
        $values = $this->statTileValues($html);

        $this->assertCount(8, $values, "Lapan kad statistik dijangka ({$context}).");
        $this->assertSame(
            array_fill(0, 8, '0'),
            $values,
            "Kesemua lapan kad statistik mesti memaparkan 0 ({$context})."
        );

        // Carta_Status dan Carta_Tren: keadaan kosong pelayan, tanpa elemen
        // sasaran carta (Requirement 4 kriteria 6, Requirement 5 kriteria 9
        // dan 11).
        $boxes = $this->chartBoxes($html);

        $this->assertCount(2, $boxes, "Dua bekas carta dijangka ({$context}).");

        foreach ($boxes as $index => $box) {
            $this->assertStringContainsString(
                'class="chart-empty"',
                $box,
                "Bekas carta #{$index} mesti membawa keadaan kosong ({$context})."
            );
            $this->assertStringContainsString(
                self::CHART_EMPTY_TEXT,
                $box,
                "Bekas carta #{$index} mesti memaparkan mesej keadaan kosong ({$context})."
            );
        }

        $this->assertSame(
            0,
            preg_match('#id="laporan-status-donut"#', $html) + preg_match('#id="laporan-trend-bar"#', $html),
            "Elemen sasaran carta tidak boleh dirender dalam keadaan kosong ({$context})."
        );

        // Jadual_Aduan: baris keadaan kosong merentas sepuluh lajur, tanpa
        // pautan penomboran (Requirement 6 kriteria 14).
        $this->assertStringContainsString(
            self::TABLE_EMPTY_TEXT,
            $html,
            "Jadual_Aduan mesti memaparkan mesej keadaan kosong ({$context})."
        );
        $this->assertStringContainsString('colspan="10"', $html, "Baris kosong mesti merentas sepuluh lajur ({$context}).");
        $this->assertStringNotContainsString('class="pagination', $html, "Pautan penomboran tidak boleh dipaparkan ({$context}).");
        $this->assertStringNotContainsString('wire:click="gotoPage', $html, "Pautan penomboran tidak boleh dipaparkan ({$context}).");
    }

    public function test_inverted_date_range_empties_every_section_without_a_server_error()
    {
        // Satu aduan yang berada dalam julat yang sah, supaya keadaan kosong
        // datang daripada julat terbalik dan bukan daripada jadual yang kosong.
        $report = $this->makeReport();

        $response = $this->actingAs($this->managementUser())->get(
            '/laporan/keseluruhan?dateFrom='.now()->addDays(10)->format('Y-m-d')
            .'&dateTo='.now()->subDays(10)->format('Y-m-d')
        );

        $response->assertOk();
        $response->assertDontSee($report->ticket_no);

        $this->assertEverySectionIsEmpty($response->getContent(), 'julat tarikh terbalik');
    }

    public function test_inverted_date_range_keeps_the_entered_dates_and_warns_in_malay()
    {
        $from = now()->addDays(10)->format('Y-m-d');
        $to = now()->subDays(10)->format('Y-m-d');

        $response = $this->actingAs($this->managementUser())
            ->get("/laporan/keseluruhan?dateFrom={$from}&dateTo={$to}");

        $response->assertOk();

        // Nilai yang dimasukkan tidak ditulis semula (Requirement 1 kriteria 9).
        $response->assertSee('wire:model.live="dateFrom"', false);
        $this->assertStringContainsString($from, $response->getContent());
        $this->assertStringContainsString($to, $response->getContent());

        // Amaran Bahasa Melayu pada pil tempoh dan pada kedua-dua kawalan
        // tarikh (Requirement 8 kriteria 12).
        $response->assertSee('report-meta--warn', false);
        $response->assertSee('Julat tarikh tidak sah: Dari Tarikh lebih lewat daripada Hingga Tarikh.');
        $this->assertSame(
            2,
            substr_count($response->getContent(), 'Julat tarikh tidak sah.'),
            'Kedua-dua kawalan tarikh mesti membawa penanda amaran sebaris.'
        );
    }

    public function test_valid_range_with_no_matching_reports_empties_every_section()
    {
        // Aduan ini berada di luar julat yang diminta, jadi julat itu sendiri
        // sah tetapi tiada Aduan_Dikira sepadan.
        $this->makeReport(2, now()->subMonths(6)->format('Y-m-d H:i:s'));

        $response = $this->actingAs($this->managementUser())->get(
            '/laporan/keseluruhan?dateFrom='.now()->startOfMonth()->format('Y-m-d')
            .'&dateTo='.now()->format('Y-m-d')
        );

        $response->assertOk();

        $this->assertEverySectionIsEmpty($response->getContent(), 'julat sah tanpa aduan sepadan');
    }

    public function test_empty_database_empties_every_section()
    {
        $response = $this->actingAs($this->managementUser())->get('/laporan/keseluruhan');

        $response->assertOk();

        $this->assertEverySectionIsEmpty($response->getContent(), 'pangkalan data kosong');
    }

    /**
     * Requirement 9 kriteria 13: id penapis yang tiada rekod sepadan ialah
     * keadaan kosong, bukan ralat pelayan. Hantaran `(int)` dalam baseQuery()
     * menukar nilai bukan angka kepada 0, yang juga memadankan sifar rekod.
     */
    #[DataProvider('nonExistentFilterProvider')]
    public function test_non_existent_filter_ids_empty_every_section_without_a_server_error(string $query, string $context)
    {
        $report = $this->makeReport();

        $response = $this->actingAs($this->managementUser())->get("/laporan/keseluruhan?{$query}");

        $response->assertOk();
        $response->assertDontSee($report->ticket_no);

        $this->assertEverySectionIsEmpty($response->getContent(), $context);
    }

    public static function nonExistentFilterProvider(): array
    {
        return [
            'id status tidak wujud' => ['status=99999', 'status=99999'],
            'id tahap risiko tidak wujud' => ['riskLevel=99999', 'riskLevel=99999'],
            'id pegawai tidak wujud' => ['pegawai=99999', 'pegawai=99999'],
            'bahagian tidak wujud' => ['bahagian=Bahagian+Tiada', 'bahagian tidak wujud'],
            'rating di luar skala' => ['rating=9', 'rating=9'],
            'ketiga-tiga id sekali' => ['status=99999&riskLevel=99999&pegawai=99999', 'gabungan id tidak wujud'],
            'nilai bukan angka' => ['status=abc&riskLevel=abc&pegawai=abc&rating=abc', 'nilai bukan angka'],
        ];
    }

    /**
     * Requirement 7 kriteria 5: ketinggian bekas carta sama dalam ketiga-tiga
     * keadaan. Ketinggian itu milik `.chart-box` sahaja, jadi ujian ini
     * mengesahkan ketiga-tiga keadaan berada DI DALAM bekas yang sama dan tiada
     * satu pun daripadanya membawa ketinggiannya sendiri.
     */
    public function test_drawn_empty_and_fallback_states_live_inside_the_same_chart_box()
    {
        // Keadaan dilukis: satu aduan dalam julat lalai.
        $this->makeReport();

        $drawn = $this->actingAs($this->managementUser())->get('/laporan/keseluruhan');
        $drawn->assertOk();

        $drawnBoxes = $this->chartBoxes($drawn->getContent());
        $this->assertCount(2, $drawnBoxes);

        foreach ($drawnBoxes as $index => $box) {
            // Sasaran carta dan mesej fallback ialah adik-beradik dalam bekas
            // yang sama, jadi kedua-duanya mewarisi ketinggian tetap itu.
            $this->assertMatchesRegularExpression(
                '#id="laporan-(status-donut|trend-bar)" style="width: 100%; height: 100%;"#',
                $box,
                "Bekas carta #{$index} mesti mengandungi sasaran carta yang mengisi ketinggian penuh."
            );
            $this->assertStringContainsString('class="chart-fallback"', $box);
            $this->assertStringContainsString(self::CHART_FALLBACK_TEXT, $box);
            $this->assertStringNotContainsString('class="chart-empty"', $box);
        }

        // Keadaan kosong: bekas yang sama, keadaan kosong dan fallback di
        // dalamnya, tanpa sasaran carta.
        $empty = $this->actingAs($this->managementUser())->get(
            '/laporan/keseluruhan?dateFrom='.now()->addDay()->format('Y-m-d')
            .'&dateTo='.now()->subDay()->format('Y-m-d')
        );
        $empty->assertOk();

        $emptyBoxes = $this->chartBoxes($empty->getContent());
        $this->assertCount(2, $emptyBoxes);

        foreach ($emptyBoxes as $index => $box) {
            $this->assertStringContainsString('class="chart-empty"', $box);
            $this->assertStringContainsString('class="chart-fallback"', $box);
            $this->assertStringContainsString(self::CHART_FALLBACK_TEXT, $box);
            $this->assertStringNotContainsString(
                'height:',
                str_replace('class="chart-empty"', '', $box),
                "Keadaan kosong bekas #{$index} tidak boleh menetapkan ketinggiannya sendiri."
            );
        }

        // Ketinggian tetap itu diisytiharkan sekali, pada `.chart-box`: keadaan
        // kosong mengisi 100% dan fallback ialah tindanan `inset: 0`, jadi tiada
        // satu pun daripada tiga keadaan boleh menukar ketinggian bekas.
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertSame(
            1,
            preg_match('#\.chart-box\s*\{[^}]*height:\s*300px#s', $css),
            '.chart-box mesti mengisytiharkan ketinggian tetap 300px.'
        );
        $this->assertSame(
            1,
            preg_match('#@media \(max-width: 991\.98px\) \{ \.chart-box \{ height: 260px; \} \}#', $css),
            '.chart-box mesti jatuh kepada 260px pada <=991px.'
        );
        $this->assertSame(
            1,
            preg_match('#\.chart-empty\s*\{[^}]*height:\s*100%#s', $css),
            '.chart-empty mesti mengisi ketinggian penuh bekas, bukan menetapkan ketinggian sendiri.'
        );
        $this->assertSame(
            1,
            preg_match('#\.chart-fallback\s*\{[^}]*position:\s*absolute;[^}]*inset:\s*0#s', $css),
            '.chart-fallback mesti menjadi tindanan inset: 0 dalam bekas, tanpa ketinggian sendiri.'
        );
    }

    /**
     * Requirement 8 kriteria 12: setiap mesej keadaan kosong dalam Bahasa
     * Melayu, dan ia datang daripada view (bukan daripada data), jadi ia tidak
     * boleh bertukar bahasa apabila data berubah.
     */
    public function test_every_empty_state_message_is_in_malay()
    {
        $response = $this->actingAs($this->managementUser())->get(
            '/laporan/keseluruhan?dateFrom='.now()->addDay()->format('Y-m-d')
            .'&dateTo='.now()->subDay()->format('Y-m-d')
        );

        $response->assertOk();

        $response->assertSee(self::CHART_EMPTY_TEXT);
        $response->assertSee(self::TABLE_EMPTY_TEXT);
        $response->assertSee(self::CHART_FALLBACK_TEXT);
        $response->assertSee('Julat tarikh tidak sah.');

        $content = $response->getContent();

        // Tiada mesej keadaan kosong lalai Bahasa Inggeris yang terlepas.
        foreach (['No data', 'No records', 'Not found', 'No results'] as $english) {
            $this->assertStringNotContainsStringIgnoringCase($english, $content);
        }
    }
}
