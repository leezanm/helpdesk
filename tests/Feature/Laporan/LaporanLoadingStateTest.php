<?php

namespace Tests\Feature\Laporan;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\ReportStatuses;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FeatureTestCase;

/**
 * Keadaan memuat Laporan Keseluruhan.
 * Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 16.2, dahulunya 12.3)
 *
 * Tabir memuat dan atribut `disabled` ialah markup semata-mata: Livewire
 * menghidupkannya di penyemak imbas, jadi PHPUnit tidak boleh melihat tabir
 * yang benar-benar muncul. Yang BOLEH diuji, dan yang tiada ujian lain
 * mengujinya hari ini, ialah bahawa atribut tersebut dipancarkan pada elemen
 * yang BETUL dan dengan nilai sasaran yang betul — tanpa ujian ini, membuang
 * `wire:loading.flex` atau menyilangkan `wire:target` butang eksport dengan
 * senarai penapis akan lulus dengan senyap.
 *
 * Setiap pengesahan di sini mengikat atribut kepada elemennya (tag pembuka
 * yang ditangkap melalui regex), bukan kepada badan halaman, kerana
 * `assertSee('wire:loading.flex')` akan lulus walaupun atribut itu mendarat
 * pada nod yang salah.
 *
 * Keadaan kosong: `tests/Feature/Laporan/LaporanEmptyStateTest.php` sudah
 * mengesahkan lapan kad bernilai 0, kedua-dua bekas carta dan baris jadual
 * kosong secara menyeluruh bagi ketiga-tiga punca (jumlah sifar, julat
 * terbalik, id penapis yang tidak wujud). Fail ini TIDAK mengulanginya; ia
 * hanya menguji apa yang tiada di sana, iaitu interaksi antara kedua-dua
 * keadaan: tabir memuat mesti kekal dirender dalam keadaan kosong, kerana
 * tabir yang tersembunyi di dalam cabang "ada data" bermakna tiada maklum
 * balas memuat semasa pengguna menapis keluar daripada keadaan kosong.
 *
 * Requirement 7 kriteria 1 dan 6, Requirement 9 kriteria 12 dan 13,
 * Requirement 11 kriteria 13.
 */
class LaporanLoadingStateTest extends FeatureTestCase
{
    /**
     * Setiap pencetus permintaan pada halaman ini: ketujuh penapis, butang
     * "Set Semula" dan ketiga-tiga kaedah WithPagination.
     *
     * Dibandingkan sebagai set (disusun sebelum dibandingkan), jadi menyusun
     * semula senarai `$busy` dalam view tidak memecahkan ujian, tetapi
     * MENINGGALKAN atau MENAMBAH pencetus memecahkannya.
     */
    private const BUSY_TRIGGERS = [
        'dateFrom', 'dateTo', 'bahagian', 'status', 'pegawai', 'rating', 'riskLevel',
        'resetFilters',
        'gotoPage', 'previousPage', 'nextPage',
    ];

    private const TABLE_EMPTY_TEXT = 'Tiada aduan sepadan dengan penapis.';

    private const CHART_EMPTY_TEXT = 'Tiada data untuk tempoh ini';

    private function managementUser(): User
    {
        $user = User::factory()->create();
        $user->syncRoles(['helpdesk.admin']);

        return $user;
    }

    /** Halaman sebenar melalui HTTP, sebagai pengguna dengan `access-main-dashboard`. */
    private function pageHtml(string $query = ''): string
    {
        $response = $this->actingAs($this->managementUser())
            ->get('/laporan/keseluruhan'.($query === '' ? '' : "?{$query}"));

        $response->assertOk();

        return $response->getContent();
    }

    private function makeReport(int $statusId = ReportStatuses::DISEMAK): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);
        Auth::login(User::factory()->create());

        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = ReportStatuses::DISEMAK;
        $report->submitted_at = now();
        $report->save();

        if ($statusId !== ReportStatuses::DISEMAK) {
            $report->report_status_id = $statusId;
            $report->save();
        }

        Auth::logout();

        return $report->fresh();
    }

    /**
     * Nilai `wire:target` setiap tabir memuat, mengikut susunan ia dirender.
     *
     * Regex menuntut tabir menjadi anak PERTAMA pembalut `.report-busy`-nya
     * dan membawa kedua-dua `wire:loading.flex` serta `wire:target` pada tag
     * yang sama. Jadi tabir yang kehilangan `wire:loading.flex`, atau yang
     * terkeluar daripada pembalutnya (tiada pembalut bermakna tiada bekas
     * `position: relative`, iaitu tindanan yang melayang di tempat lain pada
     * halaman), tidak akan ditangkap di sini sama sekali.
     *
     * @return array<int, string>
     */
    private function veilTargets(string $html): array
    {
        preg_match_all(
            '#<div class="report-busy(?:\s[^"]*)?">\s*<div class="report-busy__veil"\s+wire:loading\.flex\s+wire:target="([^"]+)"\s*>#',
            $html,
            $matches
        );

        // Tiada tabir yang terlepas: bilangan yang ditangkap mesti sama dengan
        // bilangan penanda kelas tabir dalam keseluruhan halaman.
        $this->assertSame(
            substr_count($html, 'report-busy__veil'),
            count($matches[1]),
            'Setiap .report-busy__veil mesti menjadi anak pertama pembalut .report-busy dan membawa wire:loading.flex beserta wire:target.'
        );

        return $matches[1];
    }

    /**
     * Kandungan setiap pembalut `.report-busy`, dipecahkan pada tag pembuka
     * pembalut. Digunakan untuk mengikat setiap tabir kepada BAHAGIAN yang
     * sepatutnya ditutupnya.
     *
     * @return array<int, string>
     */
    private function busySections(string $html): array
    {
        $sections = preg_split('#<div class="report-busy(?:\s[^"]*)?">#', $html);
        array_shift($sections);

        return $sections;
    }

    /** Tag pembuka butang dengan tindakan `wire:click` yang diberi, tepat satu. */
    private function buttonTag(string $html, string $action): string
    {
        preg_match_all('#<button[^>]*wire:click="'.preg_quote($action, '#').'"[^>]*>#', $html, $matches);

        $this->assertCount(
            1,
            $matches[0],
            "Tepat satu butang wire:click=\"{$action}\" dijangka pada halaman."
        );

        return $matches[0][0];
    }

    /**
     * Requirement 7 kriteria 1: tabir pada ketiga-tiga bahagian, dengan
     * sasaran yang sama supaya ia muncul dan hilang serentak.
     */
    public function test_the_three_loading_veils_share_one_identical_target()
    {
        $targets = $this->veilTargets($this->pageHtml());

        $this->assertCount(3, $targets, 'Tiga tabir memuat dijangka: Kad_Statistik, baris carta dan kad Jadual_Aduan.');
        $this->assertCount(
            1,
            array_unique($targets),
            'Ketiga-tiga tabir mesti berkongsi nilai wire:target yang SAMA, jika tidak ia tidak muncul dan hilang serentak.'
        );
    }

    /**
     * Requirement 7 kriteria 1: ketiga-tiga tabir menutup tiga bahagian yang
     * BERBEZA. Tanpa pengesahan ini, tiga tabir yang bertindan di atas satu
     * bahagian yang sama akan lulus ujian di atas.
     */
    public function test_each_veil_covers_a_different_section_of_the_page()
    {
        $sections = $this->busySections($this->pageHtml());

        $this->assertCount(3, $sections);

        // Penanda unik bagi setiap bahagian: grid kad, bekas carta, kad jadual.
        $markers = [
            'stat-grid' => 'class="stat-grid"',
            'carta' => 'class="chart-split"',
            'jadual' => 'class="card data-card"',
        ];

        foreach (array_values($markers) as $index => $expected) {
            $this->assertStringContainsString(
                $expected,
                $sections[$index],
                "Tabir #{$index} mesti menutup bahagian yang membawa {$expected}."
            );

            // Dan hanya bahagian itu: penanda dua bahagian yang lain tidak
            // boleh berada dalam pembalut yang sama, jika tidak satu tabir
            // menutup lebih daripada satu bahagian.
            foreach ($markers as $other) {
                if ($other === $expected) {
                    continue;
                }

                $this->assertStringNotContainsString(
                    $other,
                    $sections[$index],
                    "Tabir #{$index} tidak boleh menutup bahagian yang membawa {$other}."
                );
            }
        }

        // Kedua-dua kad carta berada di bawah SATU tabir (Requirement 7
        // kriteria 1 mengira bahagian carta sebagai satu bahagian).
        $this->assertSame(
            2,
            substr_count($sections[1], 'class="chart-box"'),
            'Satu tabir mesti menutup kedua-dua Carta_Status dan Carta_Tren.'
        );
    }

    /**
     * Requirement 7 kriteria 1: senarai sasaran yang dikongsi merangkumi
     * SETIAP pencetus permintaan — ketujuh penapis, butang "Set Semula" dan
     * ketiga-tiga kaedah penomboran. Pencetus yang tertinggal bermakna satu
     * laluan interaksi tanpa maklum balas memuat sama sekali.
     */
    public function test_the_shared_target_lists_every_request_trigger_on_the_page()
    {
        $targets = $this->veilTargets($this->pageHtml());

        $tokens = preg_split('/\s*,\s*/', $targets[0], -1, PREG_SPLIT_NO_EMPTY);

        sort($tokens);
        $expected = self::BUSY_TRIGGERS;
        sort($expected);

        $this->assertSame($expected, $tokens, 'Senarai wire:target yang dikongsi mesti merangkumi tepat sebelas pencetus permintaan halaman ini.');
    }

    /**
     * Requirement 7 kriteria 6 dan Requirement 8 kriteria 3: butang "Set
     * Semula" menerima atribut `disabled` sebenar, dan ia sampai ke butang itu
     * melalui prop `resetBusyTarget` `x-filter-bar` dengan senarai sasaran yang
     * SAMA seperti tabir — bukan senarai kedua yang boleh menyimpang.
     */
    public function test_the_reset_button_is_disabled_by_the_same_shared_target_as_the_veils()
    {
        $html = $this->pageHtml();
        $shared = $this->veilTargets($html)[0];

        $button = $this->buttonTag($html, 'resetFilters');

        $this->assertStringContainsString(
            'wire:loading.attr="disabled"',
            $button,
            'Butang "Set Semula" mesti membawa wire:loading.attr="disabled" pada tagnya sendiri.'
        );
        $this->assertStringContainsString(
            'wire:target="'.$shared.'"',
            $button,
            'Sasaran butang "Set Semula" mesti senarai $busy yang sama seperti ketiga-tiga tabir.'
        );

        // Butang tidak dinyahaktifkan pada render pertama: `disabled` dikenakan
        // oleh Livewire semasa permintaan dalam penerbangan, bukan dipanggang
        // ke dalam markup.
        $this->assertSame(
            0,
            preg_match('#\sdisabled(?=[\s>=])#', $button),
            'Butang "Set Semula" tidak boleh dirender dengan atribut disabled statik.'
        );
    }

    /**
     * Requirement 11 kriteria 13 hingga 15: butang "Eksport CSV" disasarkan
     * SECARA BERASINGAN. Eksport yang sedang berjalan tidak boleh
     * menyahaktifkan penapis, dan penapis yang sedang dimuat tidak boleh
     * menyahaktifkan butang eksport.
     */
    public function test_the_export_button_is_disabled_by_its_own_target_only()
    {
        $html = $this->pageHtml();
        $shared = $this->veilTargets($html)[0];

        $button = $this->buttonTag($html, 'exportCsv');

        $this->assertStringContainsString('wire:loading.attr="disabled"', $button);
        $this->assertStringContainsString(
            'wire:target="exportCsv"',
            $button,
            'Butang "Eksport CSV" mesti menyasarkan tindakan exportCsv sahaja.'
        );
        $this->assertSame(
            0,
            preg_match('#\sdisabled(?=[\s>=])#', $button),
            'Butang "Eksport CSV" tidak boleh dirender dengan atribut disabled statik.'
        );

        // Kedua-dua arah pengasingan: exportCsv tiada dalam senarai yang
        // dikongsi, dan senarai yang dikongsi tiada dalam sasaran butang
        // eksport.
        $this->assertStringNotContainsString(
            'exportCsv',
            $shared,
            'exportCsv tidak boleh berada dalam senarai sasaran penapis, jika tidak eksport akan menyahaktifkan butang "Set Semula" dan menurunkan tabir.'
        );
        $this->assertStringNotContainsString(
            'dateFrom',
            $button,
            'Butang "Eksport CSV" tidak boleh menyasarkan penapis, jika tidak menukar penapis akan menyahaktifkannya.'
        );
    }

    /**
     * Interaksi antara keadaan memuat dan keadaan kosong — satu-satunya
     * bahagian keadaan kosong yang tidak diuji oleh LaporanEmptyStateTest.
     *
     * Ketiga-tiga tabir mesti kekal dirender apabila setiap bahagian kosong.
     * Tabir yang terletak di dalam cabang "ada data" (`@forelse` atau
     * `@unless`) akan hilang tepat dalam keadaan ini, jadi menapis KELUAR
     * daripada keadaan kosong akan berlaku tanpa sebarang maklum balas memuat.
     *
     * Requirement 9 kriteria 12 (julat terbalik) dan kriteria 13 (id penapis
     * yang tidak wujud).
     */
    #[DataProvider('emptyStateProvider')]
    public function test_the_loading_veils_survive_every_empty_state(string $query, bool $seedReport, string $context)
    {
        if ($seedReport) {
            // Aduan yang akan dipaparkan kalaulah penapis tidak mengosongkan
            // hasil, supaya keadaan kosong datang daripada penapis dan bukan
            // daripada jadual yang kosong.
            $this->makeReport();
        }

        $html = $this->pageHtml($query);

        $targets = $this->veilTargets($html);

        $this->assertCount(3, $targets, "Ketiga-tiga tabir mesti kekal dalam keadaan kosong ({$context}).");
        $this->assertCount(1, array_unique($targets), "Tabir mesti kekal berkongsi satu sasaran dalam keadaan kosong ({$context}).");

        // Tabir ialah TINDANAN, bukan penggantian: markup keadaan kosong
        // berada di dalam pembalut yang sama, di sebelah tabir.
        $sections = $this->busySections($html);

        $this->assertStringContainsString(
            self::CHART_EMPTY_TEXT,
            $sections[1],
            "Keadaan kosong carta mesti berada dalam pembalut tabir yang sama ({$context})."
        );
        $this->assertStringContainsString(
            self::TABLE_EMPTY_TEXT,
            $sections[2],
            "Keadaan kosong Jadual_Aduan mesti berada dalam pembalut tabir yang sama ({$context})."
        );

        // Kedua-dua butang kekal boleh dinyahaktifkan: senarai sasaran tidak
        // bergantung kepada data, jadi pengguna masih mendapat maklum balas
        // apabila menapis keluar daripada keadaan kosong.
        $this->assertStringContainsString('wire:loading.attr="disabled"', $this->buttonTag($html, 'resetFilters'));
        $this->assertStringContainsString('wire:loading.attr="disabled"', $this->buttonTag($html, 'exportCsv'));
    }

    public static function emptyStateProvider(): array
    {
        $future = now()->addDays(10)->format('Y-m-d');
        $past = now()->subDays(10)->format('Y-m-d');

        return [
            // Jumlah sifar: pangkalan data tanpa Aduan_Dikira sama sekali.
            'jumlah sifar' => ['', false, 'jumlah sifar'],
            'julat tarikh terbalik' => ["dateFrom={$future}&dateTo={$past}", true, 'julat tarikh terbalik'],
            'id penapis tidak wujud' => ['status=99999&riskLevel=99999&pegawai=99999', true, 'id penapis tidak wujud'],
        ];
    }

    /**
     * Legenda_Carta_Status bertanda `wire:ignore` (tugasan 15.2), jadi morph
     * Livewire tidak menyentuh kandungannya. Ujian ini mengesahkan bahawa ini
     * tidak membekukan legenda: bekas legenda dirender KOSONG oleh pelayan
     * (tiada baris pelayan yang boleh menjadi lapuk), dan pemegang data JSON
     * yang skrip membina baris legenda daripadanya berada DI LUAR subtree yang
     * diabaikan, jadi ia dikemas kini oleh setiap render.
     *
     * Kelakuan masa jalan — skrip melukis semula legenda pada acara
     * `laporan-charts-updated` — tidak boleh diuji dalam PHPUnit; yang boleh
     * diuji ialah bahawa data yang menjadi sumbernya sentiasa segar.
     */
    public function test_the_ignored_legend_is_server_empty_and_its_data_source_follows_the_filters()
    {
        $disemak = $this->makeReport(ReportStatuses::DISEMAK);
        $this->makeReport(ReportStatuses::SELESAI);

        $unfiltered = $this->pageHtml();

        // Bekas legenda: kosong pada pelayan, dalam `.chart-split`, bertanda
        // `wire:ignore`. Kosong ialah syaratnya — kandungan pelayan di dalam
        // elemen `wire:ignore` TIDAK akan dikemas kini oleh morph, jadi
        // apa-apa baris yang dirender di sini akan kekal lapuk selamanya.
        $this->assertSame(
            1,
            preg_match('#<div id="laporan-status-legend" class="chart-legend" wire:ignore>\s*</div>#', $unfiltered),
            'Bekas legenda mesti dirender kosong dan bertanda wire:ignore.'
        );
        $this->assertStringContainsString('class="chart-split"', $unfiltered);

        // Pemegang data JSON: bukan di dalam bekas legenda yang diabaikan, dan
        // tidak bertanda `wire:ignore` sendiri.
        $this->assertSame(
            1,
            preg_match('#<div id="laporan-chart-data"\s+data-status=\'([^\']*)\'\s+data-trend=\'([^\']*)\'\s+hidden></div>#', $unfiltered, $holder),
            'Pemegang data carta mesti dirender sebagai elemen bebas tanpa wire:ignore.'
        );

        $segments = json_decode($holder[1], true)['segments'];

        $this->assertCount(2, $segments, 'Dua status mempunyai aduan, jadi dua baris legenda dijangka.');

        // Penapis ditukar: pemegang data membawa set segmen yang BARU, jadi
        // skrip mempunyai data segar untuk membina semula legenda.
        $filtered = $this->pageHtml('status='.ReportStatuses::DISEMAK);

        preg_match('#data-status=\'([^\']*)\'#', $filtered, $filteredHolder);
        $filteredSegments = json_decode($filteredHolder[1], true)['segments'];

        $this->assertCount(1, $filteredSegments, 'Pemegang data mesti mencerminkan penapis semasa, bukan render terdahulu.');
        $this->assertSame($disemak->status->desc, $filteredSegments[0]['label']);

        // Bekas legenda masih hadir dan masih kosong selepas penapis berubah.
        $this->assertSame(
            1,
            preg_match('#<div id="laporan-status-legend" class="chart-legend" wire:ignore>\s*</div>#', $filtered)
        );
    }
}
