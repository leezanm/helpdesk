<?php

namespace Tests\Feature\Livewire\Laporan;

use App\Livewire\Laporan\ReportAll;
use App\Models\Ref\ReportStatus;
use App\Models\Ref\RiskLevel;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Support\ReportStatuses;
use Database\Seeders\RiskLevelSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\FeatureTestCase;

/**
 * Display properties of the redesigned Laporan Keseluruhan.
 *
 * Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (task 16.1, formerly 9.5).
 * It is deliberately scheduled after task 15, so every assertion below reads
 * the final markup: the scoped `.stat-tone-*` card classes from task 15.1 and
 * the `.chart-split` + `#laporan-status-legend` donut layout from task 15.2.
 *
 * This is the only automated cover for the table markup itself. The sibling
 * files assert the chart *payloads* (ReportAllDonutDataTest,
 * ReportAllTrendDataTest) and the card *figures*
 * (ReportAllStatusCountsTest); what is asserted here is the HTML that
 * actually reaches the browser, so the tests render through
 * Livewire::test(...)->html() and read the rows back out of it.
 *
 * No property based testing library is used: Requirement 9 criterion 11
 * forbids new composer entries. Each property is therefore one PHPUnit method
 * running a loop of at least 100 iterations with inputs from `fake()` under a
 * fixed seed (see setUp), and every assertion carries the generating input in
 * its message so a failure is reproducible and diagnosable without a
 * shrinking counterexample reporter.
 *
 * Rows are identified by Subjek and by database id, never by Tiket. Ticket
 * numbers are issued per submission *day* (RefNo::ticketNo() keys its sequence
 * on today()), there is no unique index on ticket_no, and aduan sharing one
 * submitted_at can legitimately be handed the same sequence number, so Tiket
 * is not a row identity.
 */
class ReportAllTablePropertiesTest extends FeatureTestCase
{
    /** Loop length of every property method (design.md: Testing Strategy). */
    private const ITERATIONS = 100;

    /** Fixed `fake()` seed, so a failing iteration can be reproduced. */
    private const SEED = 20260116;

    /**
     * The reporting window every fixture is built inside. Fixed rather than
     * relative to today, so the number of Carta_Tren buckets (31 daily bars)
     * and the long form Malay dates in the Tarikh column are stable.
     */
    private const WINDOW_FROM = '2026-03-01';

    private const WINDOW_TO = '2026-03-31';

    /** Jadual_Aduan column headings, left to right (Requirement 6.1). */
    private const COLUMNS = [
        'Bil.', 'Tiket', 'Subjek', 'Bahagian', 'Kategori',
        'Risiko', 'Tarikh', 'Status', 'Rating', 'Tindakan',
    ];

    /** Livewire pagination size of Jadual_Aduan (Requirement 6.10). */
    private const PER_PAGE = 20;

    /** Subjek truncation budget in the cell (Requirement 6.12). */
    private const SUBJECT_LIMIT = 40;

    /** Bootstrap font weight utilities, resolved to their numeric weight. */
    private const FONT_WEIGHTS = [
        'fw-lighter' => 200,
        'fw-light' => 300,
        'fw-normal' => 400,
        'fw-medium' => 500,
        'fw-semibold' => 600,
        'fw-bold' => 700,
        'fw-bolder' => 800,
    ];

    /** Default weight of a cell that carries no font weight utility. */
    private const DEFAULT_FONT_WEIGHT = 400;

    private ?User $owner = null;

    protected function setUp(): void
    {
        parent::setUp();

        // _risk_levels is not part of FeatureTestCase's base seed, and the
        // Risiko badge reads its class from there.
        $this->seed(RiskLevelSeeder::class);

        fake()->seed(self::SEED);
    }

    // ---------------------------------------------------------------- fixtures

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['helpdesk.admin']);

        return $admin;
    }

    /**
     * One shared pengadu for every fixture aduan. The observer needs a logged
     * in user (it fills report_by_id and writes the first trail); reusing one
     * keeps a 100 row fixture from creating 100 users.
     */
    private function owner(): User
    {
        return $this->owner ??= User::factory()->create();
    }

    private function category(string $desc = 'Rangkaian'): ReportCategory
    {
        return ReportCategory::firstOrCreate(['desc' => $desc], ['is_active' => true]);
    }

    /**
     * One aduan, created through the model and walked through the same status
     * lifecycle the application uses, so the observer issues ticket_no and
     * writes the first trail exactly as production does.
     *
     * The lifecycle matters for the Tiket column. ReportObserver::saving()
     * fills ticket_no only on a save that has the aduan at Baru
     * (report_status_id = 2), and that is enough in production because an
     * aduan is only ever *created* as Draf or Baru — SaveReport writes no
     * other status on create, and it is also the only writer of submitted_at,
     * which it sets exactly when the status is Baru. Every later status (3 to
     * 8) is then reached by TransitionReportStatus / SubmitFeedback on an
     * aduan that already carries its ticket. So every aduan this report can
     * list (COUNTED statuses inside a submitted_at window) has a ticket
     * number in production, and a fixture that is inserted straight at, say,
     * Selesai would hold ticket_no null — a state the application cannot
     * produce. This method therefore saves at Baru first and advances
     * afterwards.
     *
     * The clock is held at the aduan's own submitted_at for the issuing save,
     * because RefNo::ticketNo() keys the daily sequence on today(): the ticket
     * then carries the submission date like a real one, instead of all
     * fixtures collapsing onto the suite's run date.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeReport(string $subject, array $attributes = []): Report
    {
        Auth::login($this->owner());

        $report = new Report();
        $report->subject = $subject;
        $report->remark = 'x';
        $report->report_category_id = $this->category()->id;
        $report->report_status_id = ReportStatuses::BARU;
        $report->submitted_at = self::WINDOW_FROM.' 09:00:00';

        foreach ($attributes as $column => $value) {
            $report->{$column} = $value;
        }

        $targetStatus = (int) $report->report_status_id;

        // A Draf is the one status that is reached without passing Baru, so it
        // is the one fixture that must not be pushed through it.
        if ($targetStatus !== ReportStatuses::DRAF) {
            $report->report_status_id = ReportStatuses::BARU;
        }

        if (filled($report->submitted_at)) {
            Carbon::setTestNow(Carbon::parse($report->submitted_at));
        }

        $report->save();

        if ((int) $report->report_status_id !== $targetStatus) {
            $report->report_status_id = $targetStatus;
            $report->save();
        }

        Carbon::setTestNow();

        Auth::logout();

        return $report->fresh();
    }

    /** The component under test, opened on the fixed reporting window. */
    private function openPage(User $admin): Testable
    {
        return Livewire::actingAs($admin)->test(ReportAll::class, [
            'dateFrom' => self::WINDOW_FROM,
            'dateTo' => self::WINDOW_TO,
        ]);
    }

    /** A timestamp inside the reporting window, on day $day. */
    private function windowTimestamp(int $day, int $hour = 9, int $minute = 0): string
    {
        return Carbon::createFromFormat('Y-m-d', self::WINDOW_FROM)
            ->startOfDay()
            ->addDays($day - 1)
            ->setTime($hour, $minute)
            ->toDateTimeString();
    }

    /**
     * A Malay subject of exactly $length characters.
     *
     * Latin letters and spaces only, so mb_strwidth() equals the character
     * count and the 40 unit budget Str::limit() applies is unambiguous. A
     * trailing space is avoided because Str::limit() rtrim()s the head it
     * keeps, which would make the expected truncation ambiguous.
     */
    private function subjectOfLength(int $length): string
    {
        $base = 'Kerosakan pepasangan rangkaian di Bahagian Kewangan perlu tindakan segera oleh pegawai teknikal';

        while (mb_strlen($base) < $length) {
            $base .= ' '.$base;
        }

        $subject = mb_substr($base, 0, $length);

        return rtrim($subject) === $subject ? $subject : mb_substr($subject, 0, $length - 1).'n';
    }

    // ----------------------------------------------------------- html readers

    /** Jadual_Aduan heading labels, left to right. */
    private function headerLabels(string $html): array
    {
        $this->assertSame(
            1,
            preg_match('#<thead class="table-head--primary">(.*?)</thead>#s', $html, $head),
            'Jadual_Aduan mesti mempunyai satu <thead class="table-head--primary">.'
        );

        preg_match_all('#<th[^>]*>(.*?)</th>#s', $head[1], $cells);

        return array_map(fn (string $cell) => $this->text($cell), $cells[1]);
    }

    /**
     * Rendered rows keyed by report id, in render order.
     *
     * The rows carry `wire:key="lap-{id}"`, which is the only identifier in
     * the markup that is guaranteed unique per aduan (ticket_no is not).
     *
     * @return array<int, string>
     */
    private function rows(string $html): array
    {
        preg_match_all('#<tr wire:key="lap-(\d+)">(.*?)</tr>#s', $html, $matches, PREG_SET_ORDER);

        $rows = [];
        foreach ($matches as $match) {
            $rows[(int) $match[1]] = $match[2];
        }

        return $rows;
    }

    /**
     * The <td> cells of one row, left to right, each as its opening
     * attributes plus its inner HTML.
     *
     * @return array<int, array{attrs:string, html:string}>
     */
    private function cells(string $rowHtml): array
    {
        preg_match_all('#<td\b([^>]*)>(.*?)</td>#s', $rowHtml, $matches, PREG_SET_ORDER);

        return array_map(fn (array $match) => [
            'attrs' => $match[1],
            'html' => $match[2],
        ], $matches);
    }

    /**
     * Visible text of a fragment: Livewire's conditional block comments
     * dropped, tags stripped, entities decoded (so `&mdash;` reads back as the
     * em dash the no-value marker renders).
     */
    private function text(string $html): string
    {
        $clean = preg_replace('#<!--\[if (BLOCK|ENDBLOCK)\]><!\[endif\]-->#', '', $html);

        return trim(html_entity_decode(strip_tags((string) $clean), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** Value of one attribute on the first element of a fragment. */
    private function attribute(string $html, string $name): ?string
    {
        if (preg_match('#\b'.preg_quote($name, '#').'="([^"]*)"#', $html, $match) !== 1) {
            return null;
        }

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** Class list of the first <span> in a fragment, e.g. a badge. */
    private function spanClass(string $html): ?string
    {
        return preg_match('#<span class="([^"]*)"#', $html, $match) === 1 ? $match[1] : null;
    }

    /**
     * Effective font weight of a cell: the heaviest Bootstrap font weight
     * utility anywhere in the cell, or 400 when it carries none.
     */
    private function cellFontWeight(array $cell): int
    {
        preg_match_all('#\bfw-[a-z]+\b#', $cell['attrs'].' '.$cell['html'], $matches);

        $weight = self::DEFAULT_FONT_WEIGHT;
        foreach ($matches[0] as $utility) {
            if (isset(self::FONT_WEIGHTS[$utility])) {
                $weight = max($weight, self::FONT_WEIGHTS[$utility]);
            }
        }

        return $weight;
    }

    /**
     * Statistic cards keyed by status id, plus the "Jumlah" card under the key
     * 'jumlah' (the card markup carries `wire:key="stat-{id}"`).
     *
     * @return array<int|string, array{classes:string, tone:?string, label:string, value:string}>
     */
    private function statCards(string $html): array
    {
        preg_match_all(
            '#<div class="(status-tile[^"]*)" wire:key="stat-([^"]+)".*?'
            .'<div class="status-tile__label">(.*?)</div>.*?'
            .'<div class="status-tile__value">(.*?)</div>#s',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $cards = [];
        foreach ($matches as $match) {
            $key = ctype_digit($match[2]) ? (int) $match[2] : $match[2];

            $tone = null;
            foreach (preg_split('/\s+/', $match[1], -1, PREG_SPLIT_NO_EMPTY) ?: [] as $class) {
                if (str_starts_with($class, 'stat-tone-')) {
                    $tone = $class;
                }
            }

            $cards[$key] = [
                'classes' => $match[1],
                'tone' => $tone,
                'label' => $this->text($match[3]),
                'value' => $this->text($match[4]),
            ];
        }

        return $cards;
    }

    /**
     * The decoded `#laporan-chart-data` payloads.
     *
     * Blade's `@json` escapes `'`, `"`, `<` and `&` as \u sequences
     * (JSON_HEX_APOS and friends are its default flags), so the single quoted
     * attribute can be read back with a plain non-greedy match and handed
     * straight to json_decode().
     *
     * @return array{status:mixed, trend:mixed}
     */
    private function chartHolder(string $html): array
    {
        $this->assertSame(
            1,
            preg_match(
                '#<div id="laporan-chart-data"\s+data-status=\'(.*?)\'\s+data-trend=\'(.*?)\'#s',
                $html,
                $match
            ),
            'Pemegang data carta #laporan-chart-data mesti hadir tepat sekali.'
        );

        return [
            'status' => json_decode($match[1], true),
            'trend' => json_decode($match[2], true),
        ];
    }

    /**
     * Walk every page of Jadual_Aduan, collecting the rendered rows keyed by
     * report id in walk order, and failing the moment an aduan appears twice.
     *
     * @return array<int, string>
     */
    private function walkRows(Testable $component, string $context = ''): array
    {
        $rows = [];
        $page = 0;

        do {
            $component->call('gotoPage', ++$page);
            $paginator = $component->viewData('reports');

            foreach ($this->rows($component->html()) as $id => $row) {
                $this->assertArrayNotHasKey(
                    $id,
                    $rows,
                    "Aduan #{$id} dirender pada lebih daripada satu halaman. {$context}"
                );
                $rows[$id] = $row;
            }
        } while ($paginator->hasMorePages());

        return $rows;
    }

    /**
     * The ids the table should list, in the order listQuery() dictates:
     * submitted_at descending with an id descending tie breaker.
     *
     * Built with a query written here rather than reused from the component,
     * so an ordering regression inside the component cannot hide behind a
     * shared expectation.
     *
     * @param  array<string, string>  $filters
     * @return array<int, int>
     */
    private function expectedOrderedIds(array $filters = []): array
    {
        return $this->expectedQuery($filters)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * The filtered row source, mirroring ReportAll::baseQuery() for the six
     * filters these properties generate (the Pegawai filter needs a trail
     * fixture and is covered by ReportAllTest).
     *
     * @param  array<string, string>  $filters
     */
    private function expectedQuery(array $filters = [])
    {
        $filters = array_merge([
            'dateFrom' => self::WINDOW_FROM,
            'dateTo' => self::WINDOW_TO,
            'bahagian' => '',
            'status' => '',
            'rating' => '',
            'riskLevel' => '',
        ], $filters);

        return Report::query()
            ->whereIn('report_status_id', ReportStatuses::COUNTED)
            ->whereBetween('submitted_at', [
                Carbon::createFromFormat('Y-m-d', $filters['dateFrom'])->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $filters['dateTo'])->endOfDay(),
            ])
            ->when($filters['bahagian'] !== '', fn ($q) => $q->where('bahagian_pengadu', $filters['bahagian']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('report_status_id', (int) $filters['status']))
            ->when($filters['rating'] !== '', fn ($q) => $q->where('rating', (int) $filters['rating']))
            ->when($filters['riskLevel'] !== '', fn ($q) => $q->where('risk_level_id', (int) $filters['riskLevel']));
    }

    /** Readable rendering of a generated filter set for a failure message. */
    private function describe(array $filters): string
    {
        $parts = [];
        foreach ($filters as $name => $value) {
            $parts[] = $name.'='.($value === '' ? '(kosong)' : $value);
        }

        return implode(' ', $parts);
    }

    // ------------------------------------------------------------- Property 17

    /**
     * Twenty rows per page, Bil. continuing across pages, submitted_at never
     * ascending and no row skipped or repeated — for any filtered result set
     * size.
     *
     * The generated filter sets are what vary the result set size: each one
     * leaves a different number of rows, so the walk covers full pages, short
     * last pages, single page sets and empty sets.
     *
     * **Validates: Requirements 6.2, 6.10**
     */
    // Feature: laporan-keseluruhan-ui-refresh, Property 17: Penomboran dan susunan Jadual_Aduan
    public function test_pagination_and_ordering_hold_for_any_result_set_size()
    {
        $admin = $this->admin();
        $bahagians = ['Bahagian Kewangan', 'Bahagian Pengurusan', 'Bahagian ICT'];

        // 45 aduan is three pages: two full and one short. Several share a
        // submitted_at value so the orderByDesc('id') tie breaker is exercised
        // rather than assumed.
        for ($i = 1; $i <= 45; $i++) {
            $this->makeReport(sprintf('Aduan %02d', $i), [
                // Days 1..15, three aduan per day, all at the same minute.
                'submitted_at' => $this->windowTimestamp((int) ceil($i / 3)),
                'bahagian_pengadu' => $bahagians[$i % 3],
                'report_status_id' => ReportStatuses::COUNTED[$i % count(ReportStatuses::COUNTED)],
                'rating' => $i % 4 === 0 ? null : ($i % 5) + 1,
            ]);
        }

        $component = $this->openPage($admin);
        $statusIds = ReportStatuses::COUNTED;

        // submitted_at is not a cast attribute on Report, so it arrives as a
        // 'Y-m-d H:i:s' string — read once here rather than per row, and
        // compared with strcmp(), which orders that format correctly.
        $submittedAt = Report::pluck('submitted_at', 'id')->all();

        for ($iteration = 0; $iteration < self::ITERATIONS; $iteration++) {
            $filters = [
                'bahagian' => fake()->randomElement(array_merge([''], $bahagians)),
                'status' => fake()->randomElement(array_merge([''], array_map('strval', $statusIds))),
                'rating' => fake()->randomElement(['', '1', '2', '3', '4', '5']),
            ];
            $context = 'Lelaran '.$iteration.', penapis: '.$this->describe($filters);

            foreach ($filters as $name => $value) {
                $component->set($name, $value);
            }

            $expected = $this->expectedOrderedIds($filters);
            $expectedPages = max(1, (int) ceil(count($expected) / self::PER_PAGE));

            $walked = [];
            $numbering = [];
            $seenKeys = [];
            $page = 0;

            do {
                $component->call('gotoPage', ++$page);
                $paginator = $component->viewData('reports');
                $html = $component->html();
                $rows = $this->rows($html);

                // Maximum twenty rows per page.
                $this->assertLessThanOrEqual(
                    self::PER_PAGE,
                    count($rows),
                    "Halaman {$page} mesti membawa maksimum ".self::PER_PAGE." baris. {$context}"
                );
                $this->assertSame(
                    self::PER_PAGE,
                    $paginator->perPage(),
                    "Penomboran mesti 20 rekod per halaman. {$context}"
                );

                $bilangan = [];
                foreach ($rows as $id => $row) {
                    $cells = $this->cells($row);
                    $bilangan[] = (int) $this->text($cells[0]['html']);
                    $walked[] = $id;

                    // The ordering key of this row: the pair listQuery()
                    // sorts on, taken from the record the row stands for.
                    $seenKeys[] = [(string) $submittedAt[$id], $id];
                }

                // Bil. on the first row of page p is (p - 1) * 20 + 1.
                if ($bilangan !== []) {
                    $this->assertSame(
                        ($page - 1) * self::PER_PAGE + 1,
                        $bilangan[0],
                        "Bil. baris pertama halaman {$page} salah. {$context}"
                    );
                    // And it runs without a gap inside the page.
                    $this->assertSame(
                        range($bilangan[0], $bilangan[0] + count($bilangan) - 1),
                        $bilangan,
                        "Bil. mesti berturutan dalam halaman {$page}. {$context}"
                    );
                }

                $numbering = array_merge($numbering, $bilangan);
            } while ($paginator->hasMorePages());

            $this->assertSame(
                $expectedPages,
                $page,
                "Bilangan halaman yang dilalui tidak sepadan dengan saiz set hasil. {$context}"
            );

            // Nothing skipped, nothing repeated, and the order is exactly the
            // one listQuery() promises.
            $this->assertSame(
                $expected,
                $walked,
                "Set hasil yang dilalui mesti sama dengan susunan yang dijangka. {$context}"
            );

            // Bil. is 1..n across the whole pagination, not per page.
            $this->assertSame(
                $expected === [] ? [] : range(1, count($expected)),
                $numbering,
                "Bil. mesti berturutan 1..n merentas kesemua halaman. {$context}"
            );

            // submitted_at never ascends, and rows sharing a submitted_at are
            // ordered by descending id.
            for ($i = 1; $i < count($seenKeys); $i++) {
                [$previousDate, $previousId] = $seenKeys[$i - 1];
                [$currentDate, $currentId] = $seenKeys[$i];

                $this->assertGreaterThanOrEqual(
                    0,
                    strcmp($previousDate, $currentDate),
                    "submitted_at menaik antara baris {$i} dan ".($i + 1).". {$context}"
                );

                if ($previousDate === $currentDate) {
                    $this->assertGreaterThan(
                        $currentId,
                        $previousId,
                        "Pemecah seri id menurun gagal pada submitted_at yang sama. {$context}"
                    );
                }
            }
        }
    }

    /**
     * The example behind Property 17: three pages of a 45 row result set,
     * numbered 1-20, 21-40 and 41-45.
     *
     * **Validates: Requirements 6.2, 6.10**
     */
    public function test_page_two_numbering_starts_at_twenty_one()
    {
        $admin = $this->admin();

        for ($i = 1; $i <= 45; $i++) {
            $this->makeReport(sprintf('Aduan %02d', $i), [
                'submitted_at' => $this->windowTimestamp(31 - (int) ceil($i / 2), 9, $i),
            ]);
        }

        $component = $this->openPage($admin);

        $expected = [
            1 => range(1, 20),
            2 => range(21, 40),
            3 => range(41, 45),
        ];

        foreach ($expected as $page => $numbers) {
            $component->call('gotoPage', $page);

            $rows = $this->rows($component->html());
            $bilangan = array_map(
                fn (string $row) => (int) $this->text($this->cells($row)[0]['html']),
                array_values($rows)
            );

            $this->assertCount(count($numbers), $rows, "Halaman {$page} membawa bilangan baris yang salah.");
            $this->assertSame($numbers, $bilangan, "Bil. halaman {$page} salah.");
        }

        // A fourth page does not exist.
        $this->assertFalse(
            $component->call('gotoPage', 3)->viewData('reports')->hasMorePages(),
            'Set hasil 45 baris mesti berakhir pada halaman ketiga.'
        );
    }

    // ------------------------------------------------------------- Property 18

    /**
     * Every row carries all ten cells with the right content: the ticket in
     * the only heavy cell, the subject truncated at 40 characters with the
     * full text as the tooltip, the long form Malay date, the status and
     * Risiko badges, a `.cell-muted` marker wherever a field is empty, and the
     * Lihat link pointing at route('aduan.show', $report).
     *
     * The loop walks 120 generated aduan — the generated input is the aduan
     * itself, so the subject length straddles the 40 character boundary and
     * each nullable field is empty on some rows and filled on others.
     *
     * **Validates: Requirements 6.1, 6.3, 6.5, 6.6, 6.7, 6.11, 6.12, 6.13**
     */
    // Feature: laporan-keseluruhan-ui-refresh, Property 18: Setiap baris jadual lengkap
    public function test_every_row_is_complete_for_any_aduan()
    {
        $admin = $this->admin();
        $riskLevels = RiskLevel::orderBy('level')->get();
        // Kategori is a non-nullable foreign key, so the only empty Kategori an
        // aduan can really have is a category whose desc is blank.
        $blankCategory = ReportCategory::create(['desc' => '', 'is_active' => true]);
        $bahagians = ['Bahagian Kewangan', 'Bahagian Pengurusan Maklumat dan Komunikasi', 'Bahagian ICT'];

        /** @var array<int, Report> $fixtures */
        $fixtures = [];

        for ($i = 0; $i < 120; $i++) {
            // Lengths 20..60 straddle the 40 character truncation boundary.
            $subject = $this->subjectOfLength(fake()->numberBetween(20, 60));
            $risk = fake()->boolean(70) ? $riskLevels->random() : null;

            $report = $this->makeReport($subject, [
                'submitted_at' => $this->windowTimestamp(
                    fake()->numberBetween(1, 31),
                    fake()->numberBetween(0, 23),
                    fake()->numberBetween(0, 59)
                ),
                'report_status_id' => fake()->randomElement(ReportStatuses::COUNTED),
                'bahagian_pengadu' => fake()->boolean(70) ? fake()->randomElement($bahagians) : null,
                'report_category_id' => fake()->boolean(80) ? $this->category()->id : $blankCategory->id,
                'risk_level_id' => $risk?->id,
                'rating' => fake()->boolean(60) ? fake()->numberBetween(1, 5) : null,
            ]);

            $fixtures[$report->id] = $report;
        }

        $component = $this->openPage($admin);

        // The header is read once: it is the same for every row.
        $headers = $this->headerLabels($component->html());

        $this->assertSame(
            self::COLUMNS,
            $headers,
            'Jadual_Aduan mesti membawa sepuluh lajur mengikut susunan.'
        );
        // Requirement 6 criterion 1 names the replacement of the old "#"
        // label explicitly, so it is asserted as its own statement rather
        // than left implied by the list above.
        $this->assertSame('Bil.', $headers[0], 'Lajur pertama mesti berlabel "Bil.".');
        $this->assertNotContains('#', $headers, 'Label "#" lama tidak boleh kekal pada mana-mana lajur.');

        $rows = $this->walkRows($component, 'Semua halaman.');

        // Every fixture aduan is rendered exactly once; walkRows() already
        // failed on a duplicate, so sorted keys settle the rest.
        $rendered = array_keys($rows);
        sort($rendered);

        $this->assertSame(
            array_keys($fixtures),
            $rendered,
            'Setiap aduan fixture mesti dirender tepat sekali merentas kesemua halaman.'
        );

        foreach ($fixtures as $id => $report) {
            $context = sprintf(
                'Aduan #%d subjek(%d aksara)="%s" bahagian=%s kategori=%s risiko=%s rating=%s',
                $id,
                mb_strlen($report->subject),
                $report->subject,
                $report->bahagian_pengadu ?? '(kosong)',
                $report->category?->desc === '' ? '(kosong)' : $report->category?->desc,
                $report->risk_level_id ?? '(kosong)',
                $report->rating ?? '(kosong)'
            );

            $cells = $this->cells($rows[$id]);

            // Ten cells, one per column.
            $this->assertCount(10, $cells, "Baris mesti membawa sepuluh sel. {$context}");

            // Tiket: the only cell on the row at weight 600 or more.
            //
            // The non-empty check comes first on purpose. Requirement 6
            // criterion 5 is about a *number* being shown prominently, so
            // comparing the cell with a ticket_no that happened to be null
            // would pass while asserting nothing. Every aduan this report can
            // list has passed through Baru and therefore carries a ticket (see
            // makeReport()); a null here means the fixture stopped reproducing
            // the application's lifecycle, not that the cell is right.
            $this->assertNotEmpty(
                $report->ticket_no,
                "Aduan yang dihantar mesti membawa nombor tiket. {$context}"
            );
            $this->assertSame(
                $report->ticket_no,
                $this->text($cells[1]['html']),
                "Sel Tiket mesti memaparkan nombor tiket. {$context}"
            );
            $this->assertGreaterThanOrEqual(
                600,
                $this->cellFontWeight($cells[1]),
                "Sel Tiket mesti membawa berat fon >=600. {$context}"
            );
            foreach ($cells as $index => $cell) {
                if ($index === 1) {
                    continue;
                }
                $this->assertLessThan(
                    600,
                    $this->cellFontWeight($cell),
                    "Sel #{$index} tidak boleh membawa berat fon >=600. {$context}"
                );
            }

            // Subjek: truncated at 40 characters in the cell, full text as the
            // tooltip on the same cell.
            $this->assertSame(
                Str::limit($report->subject, self::SUBJECT_LIMIT),
                $this->text($cells[2]['html']),
                "Sel Subjek mesti dipendekkan kepada 40 aksara. {$context}"
            );
            $this->assertSame(
                $report->subject,
                $this->attribute($cells[2]['attrs'], 'title'),
                "Tooltip sel Subjek mesti membawa subjek penuh. {$context}"
            );

            if (mb_strlen($report->subject) > self::SUBJECT_LIMIT) {
                $this->assertStringEndsWith(
                    '...',
                    $this->text($cells[2]['html']),
                    "Subjek melebihi 40 aksara mesti membawa penanda pemendekan. {$context}"
                );
                $this->assertNotSame(
                    $report->subject,
                    $this->text($cells[2]['html']),
                    "Subjek melebihi 40 aksara tidak boleh dipaparkan penuh dalam sel. {$context}"
                );
            } else {
                $this->assertSame(
                    $report->subject,
                    $this->text($cells[2]['html']),
                    "Subjek 40 aksara dan ke bawah tidak boleh dipendekkan. {$context}"
                );
            }

            // Bahagian, Kategori, Risiko and Rating: a `.cell-muted` no-value
            // marker whenever the field is empty, never an empty cell.
            $this->assertNoValueMarker(
                $cells[3],
                filled($report->bahagian_pengadu),
                $report->bahagian_pengadu ?? '',
                "Bahagian. {$context}"
            );
            $this->assertNoValueMarker(
                $cells[4],
                filled($report->category?->desc),
                (string) $report->category?->desc,
                "Kategori. {$context}"
            );

            // Risiko: the badge class comes from _risk_levels.class.
            if ($report->riskLevel) {
                $this->assertSame(
                    $report->riskLevel->class,
                    $this->spanClass($cells[5]['html']),
                    "Badge Risiko mesti mengambil kelas daripada _risk_levels.class. {$context}"
                );
                $this->assertSame(
                    'Tahap '.$report->riskLevel->level,
                    $this->text($cells[5]['html']),
                    "Badge Risiko mesti memaparkan tahap. {$context}"
                );
                $this->assertSame(
                    $report->riskLevel->label,
                    $this->attribute($cells[5]['html'], 'title'),
                    "Tooltip badge Risiko mesti membawa label penuh. {$context}"
                );
            } else {
                $this->assertMutedCell($cells[5], "Risiko. {$context}");
            }

            // Tarikh: the existing long form Malay helper.
            $this->assertSame(
                convertDateFormat($report->submitted_at, 'LL'),
                $this->text($cells[6]['html']),
                "Sel Tarikh mesti menggunakan convertDateFormat(..., 'LL'). {$context}"
            );

            // Status: the badge class comes from _report_statuses.class.
            $this->assertSame(
                $report->status->class,
                $this->spanClass($cells[7]['html']),
                "Badge Status mesti mengambil kelas daripada _report_statuses.class. {$context}"
            );
            $this->assertSame(
                $report->status->desc,
                $this->text($cells[7]['html']),
                "Badge Status mesti memaparkan nama status. {$context}"
            );

            // Rating: five star glyphs when rated, the marker when not.
            if ($report->rating) {
                $this->assertSame(
                    5,
                    preg_match_all('#<i class="bi bi-star(-fill)?"#', $cells[8]['html']),
                    "Sel Rating mesti memaparkan lima bintang. {$context}"
                );
                $this->assertSame(
                    (int) $report->rating,
                    preg_match_all('#bi bi-star-fill#', $cells[8]['html']),
                    "Bilangan bintang terisi mesti sama dengan rating. {$context}"
                );
            } else {
                $this->assertMutedCell($cells[8], "Rating. {$context}");
            }

            // Tindakan: the Lihat link, pointing at the aduan.
            $this->assertSame(
                route('aduan.show', $report),
                $this->attribute($cells[9]['html'], 'href'),
                "Pautan Lihat mesti menuju route('aduan.show', \$report). {$context}"
            );
            $this->assertSame(
                'Lihat',
                $this->attribute($cells[9]['html'], 'title'),
                "Pautan tindakan mesti berlabel Lihat. {$context}"
            );
        }
    }

    /** A cell is either the given value or the `.cell-muted` marker. */
    private function assertNoValueMarker(array $cell, bool $filled, string $value, string $context): void
    {
        if ($filled) {
            $this->assertSame($value, $this->text($cell['html']), "Sel mesti memaparkan nilainya. {$context}");
            $this->assertStringNotContainsString(
                'cell-muted',
                $cell['html'],
                "Sel bernilai tidak boleh membawa penanda tiada nilai. {$context}"
            );

            return;
        }

        $this->assertMutedCell($cell, $context);
    }

    /** An empty field renders the marker, not an empty cell. */
    private function assertMutedCell(array $cell, string $context): void
    {
        $this->assertStringContainsString(
            'class="cell-muted"',
            $cell['html'],
            "Medan kosong mesti membawa penanda .cell-muted. {$context}"
        );
        $this->assertNotSame(
            '',
            $this->text($cell['html']),
            "Medan kosong tidak boleh menghasilkan sel kosong. {$context}"
        );
    }

    /**
     * The boundary of the Subjek truncation, stated as examples: 39 and 40
     * characters pass through untouched, 41 is cut with the full text kept as
     * the tooltip.
     *
     * **Validates: Requirements 6.12**
     */
    public function test_subject_truncation_boundary_is_forty_characters()
    {
        $admin = $this->admin();

        $expectations = [];
        foreach ([39, 40, 41, 120] as $length) {
            $subject = $this->subjectOfLength($length);
            $report = $this->makeReport($subject, [
                'submitted_at' => $this->windowTimestamp($length <= 40 ? 1 : 2, 9, $length % 60),
            ]);
            $expectations[$report->id] = [$length, $subject];
        }

        $rows = $this->rows($this->openPage($admin)->html());

        foreach ($expectations as $id => [$length, $subject]) {
            $cell = $this->cells($rows[$id])[2];

            $this->assertSame(
                $subject,
                $this->attribute($cell['attrs'], 'title'),
                "Subjek {$length} aksara mesti tersedia penuh sebagai tooltip."
            );

            if ($length <= self::SUBJECT_LIMIT) {
                $this->assertSame(
                    $subject,
                    $this->text($cell['html']),
                    "Subjek {$length} aksara tidak boleh dipendekkan."
                );
            } else {
                $this->assertSame(
                    mb_substr($subject, 0, self::SUBJECT_LIMIT).'...',
                    $this->text($cell['html']),
                    "Subjek {$length} aksara mesti dipendekkan kepada 40 aksara diikuti penanda."
                );
            }
        }
    }

    /**
     * The no-value marker, stated as an example: an aduan with no Bahagian, no
     * Kategori text, no Risiko and no Rating renders four markers and no empty
     * cell.
     *
     * **Validates: Requirements 6.13**
     */
    public function test_an_aduan_with_no_optional_field_renders_four_no_value_markers()
    {
        $admin = $this->admin();
        $blankCategory = ReportCategory::create(['desc' => '', 'is_active' => true]);

        $report = $this->makeReport('Aduan tanpa maklumat pilihan', [
            'bahagian_pengadu' => null,
            'report_category_id' => $blankCategory->id,
            'risk_level_id' => null,
            'rating' => null,
        ]);

        $cells = $this->cells($this->rows($this->openPage($admin)->html())[$report->id]);

        foreach ([3 => 'Bahagian', 4 => 'Kategori', 5 => 'Risiko', 8 => 'Rating'] as $index => $column) {
            $this->assertMutedCell($cells[$index], "Lajur {$column} bagi aduan tanpa nilai.");
            $this->assertSame(
                '—',
                $this->text($cells[$index]['html']),
                "Lajur {$column} mesti memaparkan penanda tiada nilai."
            );
        }
    }

    /**
     * The Tiket column, stated as an example together with where the number
     * comes from: ReportObserver::saving() issues it on the save that puts an
     * aduan at Baru, and it survives every status after that. So a Baru aduan
     * and one that has since moved to Selesai both show a ticket shaped like
     * RefNo::ticketNo() output, and the cell shows that stored number rather
     * than deriving one of its own.
     *
     * This is the example behind the ticket assertions inside Property 18,
     * which can only compare the cell against the record.
     *
     * **Validates: Requirements 6.5**
     */
    public function test_the_ticket_cell_shows_the_number_issued_when_the_aduan_reached_baru()
    {
        $admin = $this->admin();

        $baru = $this->makeReport('Aduan berstatus Baru', [
            'submitted_at' => $this->windowTimestamp(1),
            'report_status_id' => ReportStatuses::BARU,
        ]);

        $selesai = $this->makeReport('Aduan yang sudah selesai', [
            'submitted_at' => $this->windowTimestamp(2),
            'report_status_id' => ReportStatuses::SELESAI,
        ]);

        $rows = $this->rows($this->openPage($admin)->html());

        foreach ([$baru, $selesai] as $report) {
            // The shape RefNo::ticketNo() produces: submission date, office,
            // then the running number for that day.
            $this->assertMatchesRegularExpression(
                '#^'.Carbon::parse($report->submitted_at)->format('ymd').'/HQ/\d{3}$#',
                (string) $report->ticket_no,
                "Aduan #{$report->id} mesti membawa nombor tiket bertarikh penghantarannya."
            );

            $cell = $this->cells($rows[$report->id])[1];

            $this->assertSame(
                $report->ticket_no,
                $this->text($cell['html']),
                "Sel Tiket aduan #{$report->id} mesti memaparkan nombor tiket yang disimpan."
            );
            $this->assertGreaterThanOrEqual(
                600,
                $this->cellFontWeight($cell),
                "Sel Tiket aduan #{$report->id} mesti menonjol dengan berat fon >=600."
            );
        }
    }

    // ------------------------------------------------------------- Property 13

    /**
     * The status colour has one source. For any value of
     * `_report_statuses.class`, the Jadual_Aduan badge renders that very class
     * and the Kad_Statistik tone is the `stat-tone-*` twin of the same `bg-*`
     * token — so the two are asserted to agree with each other rather than
     * against a literal colour name. The Risiko badge is checked the same way
     * against `_risk_levels.class`.
     *
     * Both reference tables are rewritten with generated class values on every
     * iteration, which is what makes this a property of the data rather than
     * of the six colours the seeder happens to ship.
     *
     * **Validates: Requirements 3.6, 6.6, 6.7**
     */
    // Feature: laporan-keseluruhan-ui-refresh, Property 13: Warna status mempunyai satu sumber tunggal
    public function test_status_and_risk_colours_derive_from_a_single_source()
    {
        $admin = $this->admin();
        $riskLevels = RiskLevel::orderBy('level')->get();

        // One aduan per COUNTED status, each carrying a risk level, so every
        // status badge and every risk badge is on page one.
        $byStatus = [];
        foreach (ReportStatuses::COUNTED as $index => $statusId) {
            $report = $this->makeReport('Aduan status '.$statusId, [
                'submitted_at' => $this->windowTimestamp(31 - $index),
                'report_status_id' => $statusId,
                'risk_level_id' => $riskLevels[$index % $riskLevels->count()]->id,
            ]);
            $byStatus[$statusId] = $report;
        }

        $candidates = [
            'badge bg-info',
            'badge bg-primary',
            'badge bg-warning',
            'badge bg-success',
            'badge bg-danger',
            'badge bg-secondary',
            'badge bg-info text-dark',
            'badge text-dark bg-success',
            'badge rounded-pill bg-danger',
            // No bg-* token at all, and an empty class: the documented
            // fallback path of ReportAll::statusTone().
            'badge',
            '',
        ];

        $component = $this->openPage($admin);

        for ($iteration = 0; $iteration < self::ITERATIONS; $iteration++) {
            $statusClasses = [];
            foreach (ReportStatuses::COUNTED as $statusId) {
                $statusClasses[$statusId] = fake()->randomElement($candidates);
                ReportStatus::where('id', $statusId)->update(['class' => $statusClasses[$statusId]]);
            }

            $riskClasses = [];
            foreach ($riskLevels as $level) {
                $riskClasses[$level->id] = fake()->randomElement($candidates);
                RiskLevel::where('id', $level->id)->update(['class' => $riskClasses[$level->id]]);
            }

            $html = $component->call('gotoPage', 1)->html();
            $rows = $this->rows($html);
            $cards = $this->statCards($html);

            foreach ($byStatus as $statusId => $report) {
                $class = $statusClasses[$statusId];
                $context = sprintf(
                    'Lelaran %d, status #%d dengan _report_statuses.class="%s".',
                    $iteration,
                    $statusId,
                    $class
                );

                $badgeClass = $this->spanClass($this->cells($rows[$report->id])[7]['html']);

                // The badge is the database value, verbatim.
                $this->assertSame($class, $badgeClass, "Badge status mesti kelas pangkalan data. {$context}");

                // And the card tone is derived from that same badge class, not
                // from a per-status colour map.
                $this->assertArrayHasKey($statusId, $cards, "Kad statistik status hilang. {$context}");
                $this->assertSame(
                    'stat-tone-'.$this->toneSuffix($badgeClass),
                    $cards[$statusId]['tone'],
                    "Nada kad mesti diterbitkan daripada kelas badge yang sama. {$context}"
                );

                // The renamed token is a pure string substitution of the badge
                // token, so the card can never carry the Bootstrap utility
                // name that task 15.1 removed.
                $this->assertStringNotContainsString(
                    ' bg-',
                    ' '.$cards[$statusId]['classes'],
                    "Kad tidak boleh membawa kelas utiliti bg-* Bootstrap. {$context}"
                );
            }

            foreach ($byStatus as $statusId => $report) {
                $class = $riskClasses[$report->risk_level_id];
                $context = sprintf(
                    'Lelaran %d, tahap risiko #%d dengan _risk_levels.class="%s".',
                    $iteration,
                    $report->risk_level_id,
                    $class
                );

                $this->assertSame(
                    $class,
                    $this->spanClass($this->cells($rows[$report->id])[5]['html']),
                    "Badge risiko mesti kelas pangkalan data. {$context}"
                );
            }
        }
    }

    /**
     * The tone suffix a badge class yields: its first `bg-*` token with the
     * prefix stripped, or `secondary` when the class carries no `bg-*` token
     * (ReportAll::statusTone()'s documented fallback).
     */
    private function toneSuffix(?string $badgeClass): string
    {
        foreach (preg_split('/\s+/', trim((string) $badgeClass), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            if (str_starts_with($token, 'bg-')) {
                return preg_replace('/[^a-z0-9-]/', '', Str::after($token, 'bg-'));
            }
        }

        return 'secondary';
    }

    // -------------------------------------------------------------- Property 5

    /**
     * The JSON data holder round-trips the chart data: for any filter set,
     * `#laporan-chart-data`'s `data-status` and `data-trend` decode back to the
     * `donut` and `trend` payloads the component handed the view.
     *
     * Compared against `json_decode(json_encode($payload))` rather than the
     * payload itself, because that is precisely what a round trip through a
     * JSON attribute can preserve: 100.0 and 100 are the same JSON number, and
     * the assertion is about the transport, not about PHP's float literals.
     *
     * **Validates: Requirements 4.5, 5.8, 5.10**
     */
    // Feature: laporan-keseluruhan-ui-refresh, Property 5: Pemegang data JSON ialah round-trip data carta
    public function test_the_chart_data_holder_round_trips_both_chart_payloads()
    {
        $admin = $this->admin();
        $bahagians = ['Bahagian Kewangan', 'Bahagian Pengurusan', 'Bahagian ICT'];
        $riskLevels = RiskLevel::orderBy('level')->get();

        for ($i = 1; $i <= 30; $i++) {
            $this->makeReport(sprintf('Aduan %02d', $i), [
                'submitted_at' => $this->windowTimestamp(($i % 31) + 1, $i % 24),
                'report_status_id' => ReportStatuses::COUNTED[$i % count(ReportStatuses::COUNTED)],
                'bahagian_pengadu' => $bahagians[$i % 3],
                'risk_level_id' => $riskLevels[$i % $riskLevels->count()]->id,
                'rating' => ($i % 5) + 1,
            ]);
        }

        $component = $this->openPage($admin);
        $statusIds = array_map('strval', ReportStatuses::COUNTED);

        for ($iteration = 0; $iteration < self::ITERATIONS; $iteration++) {
            // Dates included: they change the Carta_Tren axis, including the
            // daily/monthly granularity flip and the empty state.
            $from = fake()->numberBetween(1, 31);
            $filters = [
                'dateFrom' => $this->windowDate($from),
                'dateTo' => $this->windowDate(fake()->numberBetween($from, 31)),
                'bahagian' => fake()->randomElement(array_merge([''], $bahagians)),
                'status' => fake()->randomElement(array_merge([''], $statusIds)),
                'rating' => fake()->randomElement(['', '1', '2', '3', '4', '5']),
                'riskLevel' => fake()->randomElement(array_merge([''], $riskLevels->pluck('id')->map('strval')->all())),
            ];
            $context = 'Lelaran '.$iteration.', penapis: '.$this->describe($filters);

            foreach ($filters as $name => $value) {
                $component->set($name, $value);
            }

            $holder = $this->chartHolder($component->html());

            $this->assertSame(
                json_decode(json_encode($component->viewData('donut')), true),
                $holder['status'],
                "data-status mesti round-trip payload donut. {$context}"
            );
            $this->assertSame(
                json_decode(json_encode($component->viewData('trend')), true),
                $holder['trend'],
                "data-trend mesti round-trip payload tren. {$context}"
            );

            // The holder is the single carrier, so it is never half written.
            $this->assertIsArray($holder['status'], "data-status mesti JSON yang sah. {$context}");
            $this->assertIsArray($holder['trend'], "data-trend mesti JSON yang sah. {$context}");

            // Every key the component produced arrives, all of them and only
            // them, in the same order — stated against the payload rather
            // than against a written out key list. The key list is not what
            // this property owns: the *shape* of the two payloads belongs to
            // ReportAllDonutDataTest and ReportAllTrendDataTest, and pinning
            // it here only produced a second place to edit when task 15.3
            // added the 'legend' key beside 'segments'. Derived this way the
            // assertion keeps its meaning — nothing is dropped in transport,
            // including that new key — without being rewritten each time the
            // payload grows.
            $this->assertSame(
                array_keys(json_decode(json_encode($component->viewData('donut')), true)),
                array_keys($holder['status']),
                "Setiap kunci payload donut mesti sampai dalam data-status. {$context}"
            );
            $this->assertSame(
                array_keys(json_decode(json_encode($component->viewData('trend')), true)),
                array_keys($holder['trend']),
                "Setiap kunci payload tren mesti sampai dalam data-trend. {$context}"
            );
        }
    }

    /** Day $day of the reporting window, as Y-m-d. */
    private function windowDate(int $day): string
    {
        return Carbon::createFromFormat('Y-m-d', self::WINDOW_FROM)
            ->startOfDay()
            ->addDays($day - 1)
            ->format('Y-m-d');
    }

    // ------------------------------------------------------------- Property 11

    /**
     * A filter change updates every display and returns to page one: for any
     * filter set and from any current page, changing one filter leaves the
     * cards, both chart payloads and the table consistent with the new filter
     * set, with the paginator back on page 1.
     *
     * Every iteration starts on page 2 on purpose — that is the state a page
     * reset can actually be observed from.
     *
     * **Validates: Requirements 2.5**
     */
    // Feature: laporan-keseluruhan-ui-refresh, Property 11: Perubahan penapis mengemas kini kesemua paparan dan kembali ke halaman pertama
    public function test_a_filter_change_updates_every_display_and_returns_to_page_one()
    {
        $admin = $this->admin();
        $bahagians = ['Bahagian Kewangan', 'Bahagian Pengurusan', 'Bahagian ICT'];
        $riskLevels = RiskLevel::orderBy('level')->get();

        // 50 aduan is three pages unfiltered, so page 2 always exists before
        // the filter change.
        for ($i = 1; $i <= 50; $i++) {
            $this->makeReport(sprintf('Aduan %02d', $i), [
                'submitted_at' => $this->windowTimestamp(($i % 31) + 1, $i % 24, $i),
                'report_status_id' => ReportStatuses::COUNTED[$i % count(ReportStatuses::COUNTED)],
                'bahagian_pengadu' => $bahagians[$i % 3],
                'risk_level_id' => $riskLevels[$i % $riskLevels->count()]->id,
                'rating' => ($i % 5) + 1,
            ]);
        }

        $statuses = ReportStatus::whereIn('id', ReportStatuses::COUNTED)->orderBy('id')->get();
        $component = $this->openPage($admin);

        $filters = [
            'dateFrom' => self::WINDOW_FROM,
            'dateTo' => self::WINDOW_TO,
            'bahagian' => '',
            'status' => '',
            'rating' => '',
            'riskLevel' => '',
        ];

        $choices = [
            'bahagian' => array_merge([''], $bahagians),
            'status' => array_merge([''], array_map('strval', ReportStatuses::COUNTED)),
            'rating' => ['', '1', '2', '3', '4', '5'],
            'riskLevel' => array_merge([''], $riskLevels->pluck('id')->map('strval')->all()),
        ];

        for ($iteration = 0; $iteration < self::ITERATIONS; $iteration++) {
            // Whatever the filter set is, the walk starts on page 2.
            $component->call('gotoPage', 2);
            $this->assertSame(
                2,
                $component->viewData('reports')->currentPage(),
                'Lelaran '.$iteration.': halaman permulaan mesti halaman 2.'
            );

            // One filter changes. The date pair is treated as one choice so the
            // range stays valid.
            $changed = fake()->randomElement(['dateFrom', 'dateTo', 'bahagian', 'status', 'rating', 'riskLevel']);

            if ($changed === 'dateFrom') {
                $filters['dateFrom'] = $this->windowDate(fake()->numberBetween(1, 15));
            } elseif ($changed === 'dateTo') {
                $filters['dateTo'] = $this->windowDate(fake()->numberBetween(16, 31));
            } else {
                $filters[$changed] = fake()->randomElement($choices[$changed]);
            }

            $component->set($changed, $filters[$changed]);

            $context = sprintf(
                'Lelaran %d, tukar %s, penapis: %s',
                $iteration,
                $changed,
                $this->describe($filters)
            );

            // Expectation computed from the database, not from the component.
            $expectedPerStatus = [];
            foreach ($statuses as $status) {
                $expectedPerStatus[(int) $status->id] = (clone $this->expectedQuery($filters))
                    ->where('report_status_id', $status->id)
                    ->count();
            }
            $expectedTotal = array_sum($expectedPerStatus);
            $expectedIds = $this->expectedOrderedIds($filters);

            $html = $component->html();

            // Back on page one.
            $this->assertSame(
                1,
                $component->viewData('reports')->currentPage(),
                "Perubahan penapis mesti kembali ke halaman pertama. {$context}"
            );

            // Kad_Statistik: eight cards, the seven statuses plus Jumlah.
            $cards = $this->statCards($html);
            $this->assertCount(8, $cards, "Lapan kad statistik dijangka. {$context}");

            foreach ($expectedPerStatus as $statusId => $expectedCount) {
                $this->assertSame(
                    number_format($expectedCount),
                    $cards[$statusId]['value'],
                    "Kad status #{$statusId} tidak mencerminkan penapis baharu. {$context}"
                );
            }
            $this->assertSame(
                number_format($expectedTotal),
                $cards['jumlah']['value'],
                "Kad Jumlah tidak mencerminkan penapis baharu. {$context}"
            );

            // Carta_Status: the centre total and the non zero segments.
            $donut = $component->viewData('donut');
            $this->assertSame($expectedTotal, $donut['total'], "Jumlah tengah donat salah. {$context}");

            $expectedSegments = [];
            foreach ($statuses as $status) {
                if ($expectedPerStatus[(int) $status->id] > 0) {
                    $expectedSegments[] = ['label' => $status->desc, 'value' => $expectedPerStatus[(int) $status->id]];
                }
            }
            $this->assertSame(
                $expectedSegments,
                array_map(fn (array $s) => ['label' => $s['label'], 'value' => $s['value']], $donut['segments']),
                "Segmen donat tidak mencerminkan penapis baharu. {$context}"
            );

            // Carta_Tren: the axis follows the range, the bars follow the data.
            $trend = $component->viewData('trend');
            $this->assertSame($expectedTotal, $trend['total'], "Jumlah tren salah. {$context}");
            $this->assertSame(
                $expectedTotal,
                array_sum($trend['values']),
                "Hasil tambah bar tren salah. {$context}"
            );
            $this->assertCount(
                (int) round(
                    Carbon::createFromFormat('Y-m-d', $filters['dateFrom'])->startOfDay()
                        ->diffInDays(Carbon::createFromFormat('Y-m-d', $filters['dateTo'])->startOfDay())
                ) + 1,
                $trend['labels'],
                "Paksi tren mesti mengikut julat tarikh baharu. {$context}"
            );

            // Both chart payloads reached the browser.
            $holder = $this->chartHolder($html);
            $this->assertSame(
                json_decode(json_encode($donut), true),
                $holder['status'],
                "Pemegang data tidak membawa donut baharu. {$context}"
            );
            $this->assertSame(
                json_decode(json_encode($trend), true),
                $holder['trend'],
                "Pemegang data tidak membawa tren baharu. {$context}"
            );

            // Jadual_Aduan: the first page of the new result set.
            $this->assertSame(
                array_slice($expectedIds, 0, self::PER_PAGE),
                array_keys($this->rows($html)),
                "Jadual_Aduan tidak mencerminkan penapis baharu. {$context}"
            );
            $this->assertSame(
                count($expectedIds),
                $component->viewData('reports')->total(),
                "Jumlah rekod penomboran salah. {$context}"
            );
        }
    }

    // ------------------------------------------------------------- chart chrome

    /**
     * The fallback message ships in the HTML of both chart containers on every
     * render, hidden by CSS, so the chart script has something to reveal when
     * the chart library is missing or the JSON cannot be parsed.
     *
     * **Validates: Requirements 4.9, 5.12**
     */
    public function test_chart_fallback_markup_is_present_in_the_html()
    {
        $admin = $this->admin();
        $this->makeReport('Aduan hari pertama');

        $html = $this->openPage($admin)->html();

        $this->assertSame(
            2,
            preg_match_all('#class="chart-fallback"#', $html),
            'Kedua-dua bekas carta mesti membawa markup .chart-fallback.'
        );
        $this->assertStringContainsString('id="laporan-status-donut-fallback"', $html);
        $this->assertStringContainsString('id="laporan-trend-bar-fallback"', $html);
        $this->assertStringContainsString('Carta tidak dapat dipaparkan.', $html);

        // Drawn state: both chart targets are rendered, and the donut carries
        // the legend list task 15.2 moved into the `.chart-split` wrapper.
        $this->assertStringContainsString('id="laporan-status-donut"', $html);
        $this->assertStringContainsString('id="laporan-trend-bar"', $html);
        $this->assertStringContainsString('class="chart-split"', $html);
        $this->assertMatchesRegularExpression(
            '#<div id="laporan-status-legend" class="chart-legend" wire:ignore>#',
            $html,
            'Legenda donat mesti dirender sebagai senarai wire:ignore dalam .chart-split.'
        );
    }

    /**
     * A zero total renders the chart empty state instead of a chart: no chart
     * target element, no legend container, and the fallback markup still
     * present in the same fixed height container.
     *
     * **Validates: Requirements 4.6, 5.9**
     */
    public function test_chart_empty_state_renders_when_the_total_is_zero()
    {
        $admin = $this->admin();
        // Inside the window, so the empty state comes from the filter and not
        // from an empty table.
        $this->makeReport('Aduan berstatus Baru', ['report_status_id' => ReportStatuses::BARU]);

        $component = $this->openPage($admin)
            ->set('status', (string) ReportStatuses::SELESAI);

        $html = $component->html();

        $this->assertSame(0, $component->viewData('donut')['total']);
        $this->assertSame(0, $component->viewData('trend')['total']);

        $this->assertSame(
            2,
            preg_match_all('#class="chart-empty"#', $html),
            'Kedua-dua carta mesti memaparkan keadaan kosong apabila jumlah sifar.'
        );
        $this->assertSame(
            2,
            preg_match_all('#Tiada data untuk tempoh ini#', $html),
            'Kedua-dua carta mesti memaparkan mesej keadaan kosong.'
        );
        $this->assertStringNotContainsString('id="laporan-status-donut"', $html);
        $this->assertStringNotContainsString('id="laporan-trend-bar"', $html);
        $this->assertStringNotContainsString('id="laporan-status-legend"', $html);

        // The fallback overlay stays in the container in the empty state too.
        $this->assertSame(2, preg_match_all('#class="chart-fallback"#', $html));

        // And the holder still carries a well formed, empty payload: no
        // segments and no legend rows either, so the seven row rule starts
        // only from a total of 1 (Requirement 4 criterion 6 against 16).
        $holder = $this->chartHolder($html);
        $this->assertSame(['total' => 0, 'segments' => [], 'legend' => []], $holder['status']);
        $this->assertSame(0, $holder['trend']['total']);
    }
}
