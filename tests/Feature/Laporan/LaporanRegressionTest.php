<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Laporan\ReportAll;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\ReportStatuses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Regression pass for Laporan Keseluruhan (spec
 * .kiro/specs/laporan-keseluruhan-ui-refresh, task 13.1).
 *
 * Two halves, deliberately in one file because they answer one question —
 * "did the UI refresh change anything it was not allowed to change?":
 *
 *  1. Behaviour that must still hold: both authorisation layers, the Draf
 *     exclusion across all four surfaces, and the 20-per-page ordering
 *     (Requirement 9 criteria 1 to 3, Requirement 11 criteria 11 and 12).
 *
 *  2. Repository constraints: no new dependency, no new migration, no new
 *     route, no edit to the shared views and CSS tokens the spec declared
 *     off limits (Requirement 9 criteria 4, 5, 8, 9, 11, 14, 15 and
 *     Requirement 10 criterion 18).
 *
 * Why the second half asserts file *content* and not `git diff`: this working
 * tree carries unrelated uncommitted work (a rebrand of resources/css/app.css
 * and an in-progress pergerakan module that legitimately adds migrations and
 * a route under routes/). A clean-tree assertion would therefore fail for
 * reasons that have nothing to do with this spec, and would turn vacuous the
 * moment the work is committed. What Requirement 9 actually forbids is a
 * *specific kind* of change — a chart or export package, a migration for this
 * feature, an export route, this page's markup leaking into the dashboard
 * views — and each of those is asserted directly below. test_git_diff_is_empty
 * _for_the_files_this_spec_never_touches keeps a narrow git check for the
 * three paths that really are clean, as a second line of defence.
 *
 * Requirement 9 criterion 10 (the existing suite still passes, nothing
 * removed or weakened) is enforced by
 * test_pre_existing_regression_suites_are_still_present_and_intact plus the
 * suite run itself; the assertions of those files are not duplicated here.
 */
class LaporanRegressionTest extends FeatureTestCase
{
    /** Repository root, for the file-content constraint assertions. */
    private function repoPath(string $relative = ''): string
    {
        return base_path($relative);
    }

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

    /** The captured CSV download, BOM stripped, parsed into rows. */
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

    // ------------------------------------------------------------------
    // Behaviour that must still hold
    // ------------------------------------------------------------------

    /**
     * Layer one: the can:access-main-dashboard gate on the laporan.* route
     * group. A pengguna gets 403 and nothing about any aduan is in the body.
     *
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_route_gate_forbids_a_user_without_the_dashboard_permission()
    {
        $report = $this->makeReport('Aduan yang tidak boleh dilihat');

        $pengguna = User::factory()->create();
        $pengguna->syncRoles(['pengguna']);

        $response = $this->actingAs($pengguna)->get('/laporan/keseluruhan');

        $response->assertForbidden();

        // None of the six page regions rendered, and no aduan data leaked.
        $response->assertDontSee('Laporan Keseluruhan');
        $response->assertDontSee('Penapis Laporan');
        $response->assertDontSee($report->ticket_no);
        $response->assertDontSee($report->subject);
    }

    /**
     * Layer two: authorize('access-main-dashboard') inside mount(). It must
     * hold on its own, i.e. even when the route gate is bypassed by driving
     * the component directly.
     *
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_mount_throws_an_authorization_exception_for_a_regular_user()
    {
        $report = $this->makeReport('Aduan yang tidak boleh dilihat');

        $pengguna = User::factory()->create();
        $pengguna->syncRoles(['pengguna']);

        // mount() itself raises the exception...
        $this->actingAs($pengguna);

        try {
            (new ReportAll())->mount();
            $this->fail('mount() mesti menolak pengguna tanpa access-main-dashboard.');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }

        // ...and through the Livewire lifecycle that exception becomes a 403
        // with no aduan data in the payload, rather than a rendered page.
        Livewire::actingAs($pengguna)
            ->test(ReportAll::class)
            ->assertForbidden()
            ->assertDontSee($report->ticket_no)
            ->assertDontSee($report->subject);
    }

    /**
     * An admin still reaches the page — otherwise the two tests above would
     * pass for a page that is broken for everyone.
     *
     * **Validates: Requirements 9.1**
     */
    public function test_a_management_role_still_reaches_the_page()
    {
        $this->actingAs($this->admin())
            ->get('/laporan/keseluruhan')
            ->assertOk()
            ->assertSee('Laporan Keseluruhan');
    }

    /**
     * Kad_Statistik carries scoped tone classes, never a Bootstrap colour
     * utility.
     *
     * This is the exact regression task 15.1 fixed. `app.css` had declared
     * the pastel tint on `.status-tile.bg-*` all along, but the markup carried
     * Bootstrap's own utility names and Bootstrap 5.3.3 declares those
     * `background-color` rules with `!important`, so the saturated fill won
     * regardless of file order or selector specificity. Asserting the class
     * names is the only way to catch the relapse from PHP: the computed value
     * only exists in a browser, and a `bg-*` class reappearing in this markup
     * would silently restore the saturated block.
     *
     * The tone still comes from `_report_statuses.class` — the seven cards
     * below carry four distinct tones because the seeder gives bg-warning to
     * both Dalam Tindakan and KIV and bg-success to both Selesai and Selesai
     * Tindakan.
     *
     * **Validates: Requirements 3.6, 3.18, 8.15**
     */
    public function test_statistic_cards_carry_scoped_tone_classes_and_no_bootstrap_colour_utility()
    {
        $html = Livewire::actingAs($this->admin())
            ->test(ReportAll::class)
            ->html();

        // Only the eight card roots: the trailing space after `status-tile`
        // excludes the `status-tile__*` children, and everything else on the
        // page (badges, buttons) legitimately uses Bootstrap utilities.
        $this->assertSame(
            8,
            preg_match_all('#<div class="status-tile ([^"]*)"#', $html, $matches),
            'Kad_Statistik tidak lagi merender lapan kad.'
        );

        $cards = array_map(
            fn (string $attr) => preg_split('/\s+/', $attr, -1, PREG_SPLIT_NO_EMPTY),
            $matches[1]
        );

        foreach ($cards as $classes) {
            $this->assertContains('status-tile--tone', $classes);

            foreach (['bg-info', 'bg-primary', 'bg-warning', 'bg-success', 'bg-danger', 'bg-secondary'] as $utility) {
                $this->assertNotContains(
                    $utility,
                    $classes,
                    "Kad statistik membawa semula utiliti warna Bootstrap `{$utility}`; isian penuh akan menang semula."
                );
            }
        }

        // Seven status cards carry a tone class; the Jumlah card carries
        // --total instead and keeps its gradient (Requirement 3 criterion 7).
        $toneClasses = array_values(array_filter(array_map(
            fn (array $classes) => collect($classes)->first(fn (string $c) => str_starts_with($c, 'stat-tone-')),
            $cards
        )));

        $this->assertCount(7, $toneClasses);
        $this->assertSame([
            'stat-tone-danger',
            'stat-tone-info',
            'stat-tone-primary',
            'stat-tone-success',
            'stat-tone-warning',
        ], collect($toneClasses)->unique()->sort()->values()->all());

        $totals = array_values(array_filter(
            $cards,
            fn (array $classes) => in_array('status-tile--total', $classes, true)
        ));

        $this->assertCount(1, $totals);
        $this->assertStringNotContainsString('stat-tone-', implode(' ', $totals[0]));
    }

    /**
     * Draf (status 1) was never submitted, so it is absent from all four
     * surfaces: the table, the eight statistic cards, both charts and the CSV.
     *
     * **Validates: Requirements 9.3, 11.4**
     */
    public function test_draf_never_appears_in_the_table_cards_charts_or_csv()
    {
        $this->travelTo(Carbon::parse('2026-03-15 10:00:00'));

        $admin = $this->admin();

        $draf = $this->makeReport('Aduan draf', [
            'report_status_id' => ReportStatuses::DRAF,
            'submitted_at' => '2026-03-10 09:00:00',
        ]);
        $submitted = $this->makeReport('Aduan dihantar', [
            'report_status_id' => ReportStatuses::BARU,
            'submitted_at' => '2026-03-10 09:00:00',
        ]);

        $component = Livewire::actingAs($admin)->test(ReportAll::class);

        // Table.
        $component->assertSee($submitted->subject)->assertDontSee($draf->subject);
        $this->assertSame(
            [$submitted->id],
            $component->viewData('reports')->pluck('id')->all()
        );

        // Cards: seven status rows, none of them Draf, and the Jumlah card
        // counts only the submitted aduan.
        $statusCounts = $component->viewData('statusCounts');
        $this->assertSame(ReportStatuses::COUNTED, array_column($statusCounts, 'id'));
        $this->assertNotContains(ReportStatuses::DRAF, array_column($statusCounts, 'id'));
        $this->assertSame(1, $component->viewData('grandTotal'));

        // Carta_Status and Carta_Tren: both totals agree with the cards, so
        // neither can be carrying the draf.
        $this->assertSame(1, $component->viewData('donut')['total']);
        $this->assertSame(1, $component->viewData('trend')['total']);
        $this->assertSame(
            ['Baru'],
            array_column($component->viewData('donut')['segments'], 'label')
        );

        // CSV: one data row, for the submitted aduan only.
        $rows = $this->parseDownload(
            $component->call('exportCsv')->effects['download']['content']
        );
        $subjects = array_column(array_slice($rows, 1), 2);
        $this->assertSame([$submitted->subject], $subjects);
    }

    /**
     * Pagination is still 20 rows per page, ordered by submitted_at
     * descending, with no row lost or repeated across pages.
     *
     * **Validates: Requirements 9.3, 11.4**
     */
    public function test_pagination_is_twenty_per_page_ordered_by_submitted_at_descending()
    {
        $this->travelTo(Carbon::parse('2026-03-15 10:00:00'));

        $admin = $this->admin();

        // 25 aduan, newest first when ordered by submitted_at descending.
        for ($i = 1; $i <= 25; $i++) {
            $this->makeReport(sprintf('Aduan %02d', $i), [
                'submitted_at' => Carbon::parse('2026-03-14 09:00:00')->subMinutes($i),
            ]);
        }

        $component = Livewire::actingAs($admin)->test(ReportAll::class);

        $page1 = $component->viewData('reports');
        $this->assertSame(20, $page1->perPage());
        $this->assertSame(25, $page1->total());
        $this->assertCount(20, $page1->items());
        $this->assertTrue($page1->hasMorePages());

        $page2 = $component->call('gotoPage', 2)->viewData('reports');
        $this->assertCount(5, $page2->items());
        $this->assertFalse($page2->hasMorePages());

        // Descending submitted_at across the concatenation of both pages.
        // Parsed rather than read as-is: submitted_at is not cast on the
        // model, so the driver hands back a string.
        $stamps = fn ($paginator) => $paginator->pluck('submitted_at')
            ->map(fn ($value) => Carbon::parse($value)->getTimestamp())
            ->all();

        $dates = array_merge($stamps($page1), $stamps($page2));
        $sorted = $dates;
        rsort($sorted);
        $this->assertSame($sorted, $dates);

        // ...and the two pages together are every aduan exactly once.
        $ids = array_merge($page1->pluck('id')->all(), $page2->pluck('id')->all());
        $this->assertCount(25, array_unique($ids));
    }

    // ------------------------------------------------------------------
    // Repository constraints
    // ------------------------------------------------------------------

    /**
     * Eksport_CSV is a component action, so the laporan route table is
     * unchanged: the same URIs and names as before, and no export URI.
     *
     * Scoped to the laporan prefix on purpose — Requirement 9 criterion 15 is
     * about *this* page adding no route, and another module may legitimately
     * own an export URI (pergerakan/eksport-csv).
     *
     * **Validates: Requirements 9.5, 9.15, 11.11**
     */
    public function test_the_laporan_route_table_is_unchanged_and_has_no_export_route()
    {
        $web = [];
        $api = [];

        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'laporan') && ! str_starts_with($uri, 'api/laporan')) {
                continue;
            }

            // No route of any kind under laporan exports anything.
            $this->assertStringNotContainsString('eksport', $uri);
            $this->assertStringNotContainsString('export', $uri);

            str_starts_with($uri, 'api/') ? $api[] = $uri : $web[] = $uri;
        }

        sort($web);
        sort($api);

        $this->assertSame([
            'laporan',
            'laporan/keseluruhan',
            'laporan/mengikut-bahagian',
            'laporan/mengikut-kategori',
            'laporan/mengikut-peranan',
            'laporan/mengikut-status-tindakan',
            'laporan/mengikut-tahap-risiko',
            'laporan/soal-selidik',
        ], $web);

        $this->assertSame([
            'api/laporan/by-category',
            'api/laporan/by-department',
            'api/laporan/by-rating',
            'api/laporan/by-risk',
            'api/laporan/by-role',
            'api/laporan/by-status',
            'api/laporan/by-user',
        ], $api);

        // The gate on the web group is part of the behaviour being preserved.
        $this->assertContains(
            'can:access-main-dashboard',
            app('router')->getRoutes()->getByName('laporan.view-by-all')->gatherMiddleware()
        );
    }

    /**
     * No dependency was added for the charts, the export or the property
     * tests: the manifests carry exactly the packages they carried before.
     *
     * An exact key comparison rather than a keyword search, so a package that
     * nobody thought to blacklist cannot slip in either.
     *
     * **Validates: Requirements 9.11, 11.19**
     */
    public function test_dependency_manifests_carry_no_new_package()
    {
        $composer = json_decode(file_get_contents($this->repoPath('composer.json')), true);

        $this->assertSame([
            'php',
            'directorytree/ldaprecord-laravel',
            'guzzlehttp/guzzle',
            'laravel-lang/lang',
            'laravel/fortify',
            'laravel/framework',
            'laravel/tinker',
            'livewire/livewire',
            'spatie/laravel-backup',
            'spatie/laravel-permission',
        ], array_keys($composer['require']));

        $this->assertSame([
            'barryvdh/laravel-debugbar',
            'fakerphp/faker',
            'laravel/sail',
            'mockery/mockery',
            'nunomaduro/collision',
            'phpunit/phpunit',
        ], array_keys($composer['require-dev']));

        $package = json_decode(file_get_contents($this->repoPath('package.json')), true);

        $this->assertSame(
            ['axios', 'laravel-vite-plugin', 'vite'],
            array_keys($package['devDependencies'])
        );
        $this->assertArrayNotHasKey('dependencies', $package);

        // No library file was vendored in beside the manifests either: the
        // charts use the ECharts build the layout already loads, and the page
        // adds no <script src> of its own. (public/assets/libs also holds
        // chart.js, jszip, pdfmake and the DataTables buttons from the old
        // Skote theme — all pre-existing, and none of them referenced here,
        // which is what Requirement 11 criterion 19 asks.)
        $this->assertFileExists($this->repoPath('public/assets/libs/echarts/echarts.min.js'));
        $this->assertStringContainsString(
            "assets/libs/echarts/echarts.min.js",
            file_get_contents($this->repoPath('resources/views/layouts/app.blade.php'))
        );

        foreach ([
            'resources/views/laporan/show_by_all.blade.php',
            'resources/views/livewire/laporan/report-all.blade.php',
        ] as $view) {
            $contents = file_get_contents($this->repoPath($view));

            $this->assertStringNotContainsString('assets/libs', $contents);
            foreach (['jszip', 'pdfmake', 'buttons', 'chart.js', 'apexcharts'] as $lib) {
                $this->assertStringNotContainsString($lib, strtolower($contents));
            }
        }
    }

    /**
     * The database schema is untouched: no migration mentions this feature.
     *
     * The directory does hold migrations from unrelated in-progress work
     * (risk levels, pergerakan), so the assertion is per-file content rather
     * than a file count — what Requirement 9 criterion 4 forbids is a
     * migration *for this page*, and this page is a read-only report over
     * columns that already existed.
     *
     * **Validates: Requirements 9.4**
     */
    public function test_no_migration_belongs_to_this_feature()
    {
        $migrations = glob($this->repoPath('database/migrations/*.php'));
        $this->assertNotEmpty($migrations);

        foreach ($migrations as $path) {
            $name = basename($path);
            $body = strtolower(file_get_contents($path));

            foreach (['laporan', 'eksport', 'export', 'csv', 'chart', 'carta'] as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    strtolower($name),
                    "Migrasi {$name} dinamakan mengikut ciri laporan/eksport."
                );
                $this->assertStringNotContainsString(
                    $needle,
                    $body,
                    "Migrasi {$name} merujuk ciri laporan/eksport."
                );
            }
        }
    }

    /**
     * The shared Blade files the spec declared off limits carry nothing from
     * this page.
     *
     * filter-field.blade.php keeps its two props — the busy-state prop went
     * on filter-bar.blade.php, which is the only shared component the spec
     * was allowed to edit. The two dashboard views must not know about this
     * page's chart ids, its data holder or its loading veil; task 9.3 copied
     * the chart chrome helpers out of the dashboard precisely so the
     * dashboard would not have to change.
     *
     * **Validates: Requirements 9.8, 11.13**
     */
    public function test_the_off_limits_shared_views_carry_nothing_from_this_page()
    {
        $filterField = file_get_contents(
            $this->repoPath('resources/views/components/filter-field.blade.php')
        );

        $this->assertStringNotContainsString('resetBusyTarget', $filterField);
        $this->assertStringNotContainsString('reset-busy-target', $filterField);
        $this->assertStringNotContainsString('wire:loading', $filterField);
        $this->assertStringNotContainsString('wire:target', $filterField);
        // Still exactly the two props it had: label and col.
        $this->assertSame(
            1,
            preg_match("/@props\(\[\s*'label'\s*=>\s*null,\s*'col'\s*=>/", $filterField)
        );

        $thisPagesMarkers = [
            'laporan-chart-data',
            'laporan-status-donut',
            'laporan-trend-bar',
            'laporan-charts-updated',
            'report-busy',
            'stat-grid',
            'table-head--primary',
            'exportCsv',
        ];

        foreach ([
            'resources/views/dashboard.blade.php',
            'resources/views/livewire/dashboard/dashboard.blade.php',
        ] as $view) {
            $contents = file_get_contents($this->repoPath($view));

            foreach ($thisPagesMarkers as $marker) {
                $this->assertStringNotContainsString(
                    $marker,
                    $contents,
                    "{$view} merujuk `{$marker}` milik Laporan_Keseluruhan."
                );
            }

            // The dashboard keeps its own data holder, untouched.
            $this->assertStringContainsString('dashboard-chart-data', $contents);
        }
    }

    /**
     * The :root token block still declares every colour token Requirement 9
     * criterion 14 names, at its original value, and holds nothing this spec
     * introduced.
     *
     * Values are asserted literally rather than diffed against HEAD: the file
     * carries an unrelated uncommitted rebrand, and these are the values the
     * requirement froze.
     *
     * **Validates: Requirements 9.14, 10.1**
     */
    public function test_app_css_root_token_block_is_unchanged()
    {
        $css = file_get_contents($this->repoPath('resources/css/app.css'));

        $this->assertSame(1, preg_match('/:root\s*\{(.*?)\n\}/s', $css, $m));
        $root = $m[1];

        $expected = [
            '--sidebar-bg' => '#ffffff',
            '--sidebar-fg' => '#686e88',
            '--sidebar-fg-active' => 'var(--c-primary-600)',
            '--sidebar-w' => '268px',
            '--c-primary' => '#5a68cf',
            '--c-primary-050' => '#eaedfc',
            '--c-butter' => '#e0ad46',
            '--c-bg' => '#f7f8fe',
            '--c-surface' => '#ffffff',
            '--c-border' => '#e4e8f6',
            '--c-text' => '#1c2138',
            '--c-text-muted' => '#6a7089',
            '--c-success' => '#3b9b86',
            '--c-warning' => '#c08f2b',
            '--c-danger' => '#d4636f',
            '--c-info' => '#3f7fc4',
        ];

        foreach ($expected as $token => $value) {
            $this->assertSame(
                1,
                preg_match('/'.preg_quote($token, '/').'\s*:\s*'.preg_quote($value, '/').'\s*;/', $root),
                "Token {$token} tidak lagi bernilai {$value} dalam blok :root."
            );
        }

        // This spec added classes, not tokens: nothing of its vocabulary is
        // in :root.
        foreach (['--report', '--stat-', '--chart', '--tile'] as $introduced) {
            $this->assertStringNotContainsString($introduced, $root);
        }
    }

    /**
     * The sidebar section title is the one and only existing declaration this
     * spec changed, and it changed to a token rather than a new literal.
     *
     * **Validates: Requirements 9.9, 10.18**
     */
    public function test_sidebar_title_colour_is_the_only_changed_existing_declaration()
    {
        $css = file_get_contents($this->repoPath('resources/css/app.css'));

        $this->assertSame(1, preg_match('/\.app-sidebar__title\s*\{(.*?)\}/s', $css, $m));
        $block = $m[1];

        $this->assertSame(1, preg_match('/color\s*:\s*var\(--c-text-muted\)\s*;/', $block));

        // The literal it replaced is no longer declared anywhere in the
        // stylesheet, so it cannot have been reinstated on another selector.
        // Comments are stripped first: the block above names #9ba1ba in prose
        // to explain the swap, and that mention must not fail the check.
        $withoutComments = preg_replace('#/\*.*?\*/#s', '', $css);
        $this->assertStringNotContainsString('#9ba1ba', $withoutComments);

        // The sidebar blocks Requirement 10 criteria 2, 4, 14 and 15 protect
        // are still there, so "only the title colour changed" is not "the
        // sidebar was rewritten".
        foreach ([
            '.app-sidebar {',
            '.app-sidebar__brand',
            '.app-main {',
            '.app-nav-link {',
            '.app-nav-link.active',
            '.app-nav-sub',
            'translateX(-100%)',
        ] as $preserved) {
            $this->assertStringContainsString($preserved, $css);
        }

        // Requirement 9 criterion 9: the page's styles arrived as new classes.
        foreach ([
            '.report-head__sub',
            '.report-meta',
            '.report-notice',
            '.stat-grid',
            '.status-tile__head',
            '.status-tile__icon',
            // Nada_Pastel Kad_Statistik (tugasan 15.1). Nama kelas berskop,
            // bukan utiliti warna Bootstrap, supaya nilai dikira tidak boleh
            // diganti oleh `!important` Bootstrap (Requirement 8 kriteria 15).
            '.status-tile--tone',
            '.stat-tone-info',
            '.stat-tone-primary',
            '.stat-tone-warning',
            '.stat-tone-success',
            '.stat-tone-danger',
            '.stat-tone-secondary',
            '.chart-box',
            '.chart-empty',
            '.chart-fallback',
            '.report-busy',
            '.report-busy__veil',
            '.table-head--primary',
            '.cell-muted',
        ] as $added) {
            $this->assertStringContainsString($added, $css);
        }
    }

    /**
     * The suites that guarded this page before the refresh are all still
     * present, and ReportAllTest still carries its six test methods.
     *
     * Their assertions are not repeated here; that they pass is established
     * by running the suite (Requirement 9 criterion 10). What this locks is
     * that none of them was deleted or hollowed out to make room for the new
     * behaviour.
     *
     * **Validates: Requirements 9.10**
     */
    public function test_pre_existing_regression_suites_are_still_present_and_intact()
    {
        foreach ([
            'tests/Feature/Livewire/Laporan/ReportAllTest.php',
            'tests/Feature/Laporan/ViewLaporanTest.php',
            'tests/Feature/SmokeTest.php',
            'tests/Feature/DashboardRoutingTest.php',
            'tests/Feature/ReportTotalsTest.php',
            'tests/Feature/Api/GetUserLookupTest.php',
        ] as $path) {
            $this->assertFileExists($this->repoPath($path));
        }

        $reportAllTest = file_get_contents(
            $this->repoPath('tests/Feature/Livewire/Laporan/ReportAllTest.php')
        );

        $methods = [
            'test_lists_reports_in_default_range',
            'test_filter_by_status',
            'test_filter_by_bahagian',
            'test_filter_by_pegawai',
            'test_date_range_excludes_outside',
            'test_filter_by_rating',
        ];

        foreach ($methods as $method) {
            $this->assertStringContainsString("function {$method}(", $reportAllTest);
        }

        // Six and only six, so a method cannot have been renamed away.
        $this->assertSame(
            count($methods),
            preg_match_all('/public function test_/', $reportAllTest)
        );

        // tests/Feature/Api/* is a directory, not a single file.
        $this->assertNotEmpty(glob($this->repoPath('tests/Feature/Api/*Test.php')));
    }

    /**
     * Second line of defence for the three constrained paths that really are
     * clean in this tree: nothing in them is modified relative to HEAD.
     *
     * Narrow on purpose. routes/ and the two dashboard views are excluded
     * because they carry unrelated uncommitted work, which is why the content
     * assertions above are the primary guard; this only adds the stronger
     * "byte for byte identical" check where it can be made honestly.
     *
     * **Validates: Requirements 9.8, 9.11**
     */
    public function test_git_diff_is_empty_for_the_files_this_spec_never_touches()
    {
        exec('git -C '.escapeshellarg(base_path()).' rev-parse --is-inside-work-tree 2>/dev/null', $out, $code);

        if ($code !== 0) {
            $this->markTestSkipped('Bukan repositori git — semakan kandungan fail di atas ialah pengadang utama.');
        }

        $paths = [
            'composer.json',
            'package.json',
            'resources/views/components/filter-field.blade.php',
        ];

        foreach ($paths as $path) {
            $names = [];
            exec(
                'git -C '.escapeshellarg(base_path())
                .' diff --name-only HEAD -- '.escapeshellarg($path),
                $names
            );

            $this->assertSame(
                [],
                array_values(array_filter($names)),
                "{$path} diubah oleh spec ini walaupun ia di luar skop."
            );
        }
    }
}
