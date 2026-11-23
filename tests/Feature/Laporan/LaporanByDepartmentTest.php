<?php

namespace Tests\Feature\Laporan;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\FeatureTestCase;

/**
 * Laporan "Jumlah Aduan Mengikut Bahagian" — kumpulan 18 dalam
 * .kiro/specs/laporan-keseluruhan-ui-refresh/tasks.md (tugasan 18.2).
 *
 * Kumpulan 18 berada DI LUAR skop spec `laporan-keseluruhan-ui-refresh`; ia
 * ialah pepijat bersebelahan yang diluluskan pengguna semasa semakan spec.
 *
 * Tugasan 18.1 menulis semula agregat bahagian dalam
 * LaporanController::departmentCounts(). Versi terdahulu merujuk alias senarai
 * select dari dalam WHERE subkueri:
 *
 *     distinct(ldap_bahagian) as bahagian,
 *     (select count(id) from reports
 *       where bahagian_pengadu = bahagian and report_status_id <> 1) as jumlah
 *
 * Itu sambungan MySQL. SQLite menolaknya, jadi `GET /laporan/mengikut-bahagian`
 * menghasilkan 500 di bawah sambungan ujian yang ditetapkan phpunit.xml.
 * Gantinya ialah subkueri berkorelasi yang mengulang ungkapan
 * `users.ldap_bahagian` dan bukan aliasnya.
 *
 * Penulisan semula itu membawa satu risiko yang perlu dikunci: `users` ialah
 * sisi "banyak" di sini — beberapa pengguna berkongsi satu bahagian — jadi
 * sebarang bentuk JOIN ke atas `reports` akan menggandakan kiraan dengan
 * bilangan pengguna dalam bahagian tersebut. Subkueri berkorelasi dipilih
 * tepat untuk mengelak itu, jadi
 * test_several_users_in_one_bahagian_collapse_to_a_single_unmultiplied_row
 * ialah ujian regresi utama fail ini.
 *
 * Pengesahan di sini dibuat ke atas `viewData('laporans')` dan ke atas JSON
 * API, bukan ke atas HTML, kerana yang diuji ialah kontrak hasil pertanyaan:
 * satu baris per bahagian dengan medan `bahagian` dan `jumlah`, disusun menaik,
 * Draf dikecualikan. `resources/views/laporan/show_by_department.blade.php`
 * memanggil `$laporans->sum('jumlah')` ke atas hasil itu.
 */
class LaporanByDepartmentTest extends FeatureTestCase
{
    private const PATH = '/laporan/mengikut-bahagian';

    /** Draf — satu-satunya status yang dikecualikan daripada kiraan. */
    private const DRAF = 1;

    /**
     * Pengguna yang memegang `access-main-dashboard`, iaitu kebenaran yang
     * dituntut oleh kumpulan laluan `laporan` dalam routes/web.php.
     *
     * `ldap_bahagian` dikekalkan NULL secara lalai supaya pengguna yang
     * bertindak tidak menambah bahagian yang tidak diduga kepada agregat;
     * setiap ujian mengisytiharkan sendiri bahagian yang ia jangka.
     */
    private function managementUser(?string $bahagian = null): User
    {
        $user = User::factory()->create(['ldap_bahagian' => $bahagian]);
        $user->syncRoles(['helpdesk.admin']);

        return $user->fresh();
    }

    /** Warga biasa: tiada `access-main-dashboard`. */
    private function plainUser(): User
    {
        $user = User::factory()->create();
        $user->syncRoles(['pengguna']);

        return $user->fresh();
    }

    /** Pengguna yang hanya menyumbang satu bahagian kepada agregat. */
    private function userInBahagian(?string $bahagian): User
    {
        return User::factory()->create(['ldap_bahagian' => $bahagian]);
    }

    /**
     * Satu aduan dengan `bahagian_pengadu` dan status yang ditetapkan.
     *
     * ReportObserver memerlukan pengguna yang log masuk (ia menetapkan
     * `report_by_id` dan menulis trail pertama), jadi aduan dicipta di bawah
     * sesi sementara seperti yang dilakukan oleh LaporanEmptyStateTest. Status
     * selain Draf/Baru ditetapkan selepas cipta, kerana observer hanya mengenal
     * dua status awal itu.
     */
    private function makeReport(?string $bahagian, int $statusId = 2): Report
    {
        $category = ReportCategory::firstOrCreate(['desc' => 'Rangkaian'], ['is_active' => true]);

        $author = User::factory()->create(['ldap_bahagian' => null]);
        Auth::login($author);

        $report = new Report();
        $report->subject = 'Ujian '.uniqid();
        $report->remark = 'x';
        $report->report_category_id = $category->id;
        $report->report_status_id = $statusId === self::DRAF ? self::DRAF : 2;
        $report->bahagian_pengadu = $bahagian;
        $report->submitted_at = now();
        $report->save();

        if ($statusId !== self::DRAF && $statusId !== 2) {
            $report->report_status_id = $statusId;
            $report->save();
        }

        Auth::logout();

        return $report->fresh();
    }

    /**
     * Baris agregat sebagai peta bahagian => jumlah, dengan bahagian NULL
     * dikunci sebagai rentetan kosong supaya ia boleh dibandingkan.
     *
     * @return array<string, mixed>
     */
    private function counts(\Illuminate\Support\Collection $laporans): array
    {
        $map = [];

        foreach ($laporans as $row) {
            $map[$row->bahagian ?? ''] = $row->jumlah;
        }

        return $map;
    }

    // ------------------------------------------------------------------
    // Laluan dan kebenaran
    // ------------------------------------------------------------------

    /**
     * Pengesahan asas tugasan 18.1: halaman menghasilkan 200 di bawah pemacu
     * SQLite yang dikonfigurasi oleh phpunit.xml. Sebelum penulisan semula,
     * permintaan yang sama menghasilkan 500 kerana pertanyaan alias-dalam-
     * subkueri ditolak pemacu.
     */
    public function test_the_department_report_renders_for_a_user_with_the_required_permission()
    {
        $this->makeReport('Bahagian Pengurusan Maklumat');

        $response = $this->actingAs($this->managementUser())->get(self::PATH);

        $response->assertOk();
        $response->assertSee('Jumlah Aduan Mengikut Bahagian');
        $response->assertSee('Bahagian Pengurusan Maklumat');
    }

    /**
     * Laluan `laporan` dikawal oleh `can:access-main-dashboard`, jadi warga
     * biasa tidak boleh melihat data bahagian seluruh organisasi.
     */
    public function test_the_department_report_is_rejected_for_a_user_without_the_permission()
    {
        $report = $this->makeReport('Bahagian Pengurusan Maklumat');

        $response = $this->actingAs($this->plainUser())->get(self::PATH);

        $response->assertForbidden();
        $response->assertDontSee('Bahagian Pengurusan Maklumat');
        $response->assertDontSee($report->ticket_no);
    }

    // ------------------------------------------------------------------
    // Ketepatan kiraan
    // ------------------------------------------------------------------

    /**
     * Kiraan per-bahagian betul merentas beberapa bahagian dan beberapa
     * status: setiap baris sama dengan bilangan aduan bukan Draf bahagian itu.
     */
    public function test_per_bahagian_counts_are_correct_across_several_bahagian_and_statuses()
    {
        $this->userInBahagian('Bahagian A');
        $this->userInBahagian('Bahagian B');
        $this->userInBahagian('Bahagian C');

        // Bahagian A: 4 bukan Draf merentas empat status berbeza.
        foreach ([2, 4, 5, 8] as $status) {
            $this->makeReport('Bahagian A', $status);
        }

        // Bahagian B: 2 bukan Draf.
        $this->makeReport('Bahagian B', 3);
        $this->makeReport('Bahagian B', 6);

        // Bahagian C: 1 bukan Draf.
        $this->makeReport('Bahagian C', 7);

        $response = $this->actingAs($this->managementUser())->get(self::PATH);
        $response->assertOk();

        $counts = $this->counts($response->viewData('laporans'));

        $this->assertSame(4, (int) $counts['Bahagian A']);
        $this->assertSame(2, (int) $counts['Bahagian B']);
        $this->assertSame(1, (int) $counts['Bahagian C']);

        // Jumlah keseluruhan yang dipaparkan tfoot view ialah hasil tambah
        // baris, jadi ia mesti sama dengan bilangan aduan bukan Draf.
        $this->assertSame(
            Report::where('report_status_id', '<>', self::DRAF)->count(),
            (int) $response->viewData('laporans')->sum('jumlah')
        );
    }

    /**
     * Draf (status 1) dikecualikan. Bahagian B hanya membawa Draf, jadi ia
     * mesti memaparkan 0 dan bukan dihitung bersama.
     */
    public function test_draf_reports_are_excluded_from_the_counts()
    {
        $this->userInBahagian('Bahagian A');
        $this->userInBahagian('Bahagian B');

        $this->makeReport('Bahagian A', 2);
        $this->makeReport('Bahagian A', self::DRAF);
        $this->makeReport('Bahagian A', self::DRAF);
        $this->makeReport('Bahagian B', self::DRAF);

        $response = $this->actingAs($this->managementUser())->get(self::PATH);
        $response->assertOk();

        $counts = $this->counts($response->viewData('laporans'));

        $this->assertSame(1, (int) $counts['Bahagian A'], 'Draf Bahagian A tidak boleh dikira.');
        $this->assertSame(0, (int) $counts['Bahagian B'], 'Bahagian yang hanya membawa Draf mesti 0.');
    }

    /**
     * Bahagian tanpa satu pun aduan bukan Draf tetap hadir dengan `0` — bukan
     * hilang daripada senarai, dan bukan NULL. Nilai NULL akan memaparkan sel
     * kosong dalam view dan merosakkan `$laporans->sum('jumlah')`.
     */
    public function test_a_bahagian_with_no_non_draf_reports_still_appears_with_zero()
    {
        $this->userInBahagian('Bahagian Berdata');
        $this->userInBahagian('Bahagian Kosong');

        $this->makeReport('Bahagian Berdata', 2);

        $response = $this->actingAs($this->managementUser())->get(self::PATH);
        $response->assertOk();

        $laporans = $response->viewData('laporans');
        $counts = $this->counts($laporans);

        $this->assertArrayHasKey(
            'Bahagian Kosong',
            $counts,
            'Bahagian tanpa aduan mesti kekal hadir dalam senarai.'
        );

        $row = $laporans->firstWhere('bahagian', 'Bahagian Kosong');

        $this->assertNotNull($row->jumlah, '`jumlah` tidak boleh NULL bagi bahagian tanpa aduan.');
        $this->assertSame(0, (int) $row->jumlah);
        $this->assertSame('0', (string) $row->jumlah, 'View memaparkan nilai ini terus; ia mesti "0".');
    }

    /**
     * Pengguna tanpa `ldap_bahagian` menghasilkan baris bahagian NULL, dengan
     * kiraannya, sama seperti kelakuan pertanyaan lama.
     *
     * Kiraan itu ialah 0 walaupun terdapat aduan yang `bahagian_pengadu`-nya
     * NULL, kerana `NULL = NULL` tidak pernah benar dalam SQL — dan itulah
     * tepat apa yang pertanyaan lama lakukan apabila aliasnya NULL. Ini
     * dinyatakan di sini supaya penulisan semula pada masa depan yang
     * "membetulkan" baris NULL supaya mengira aduan tanpa bahagian menjadi
     * perubahan kelakuan yang disengajakan dan bukan kebetulan.
     */
    public function test_users_without_a_bahagian_still_produce_the_null_bahagian_row()
    {
        $this->userInBahagian(null);
        $this->userInBahagian('Bahagian A');

        $this->makeReport('Bahagian A', 2);
        // Aduan tanpa bahagian: milik baris NULL secara konsep, tetapi tidak
        // dikira oleh perbandingan NULL — seperti sebelum ini.
        $this->makeReport(null, 2);
        $this->makeReport(null, 5);

        $response = $this->actingAs($this->managementUser())->get(self::PATH);
        $response->assertOk();

        $laporans = $response->viewData('laporans');

        $nullRows = $laporans->filter(fn ($row) => $row->bahagian === null)->values();

        $this->assertCount(1, $nullRows, 'Tepat satu baris bahagian NULL dijangka.');
        $this->assertSame(0, (int) $nullRows[0]->jumlah);
        $this->assertSame(1, (int) $this->counts($laporans)['Bahagian A']);
    }

    /**
     * Ujian regresi utama fail ini.
     *
     * `users` ialah sisi "banyak": beberapa pengguna berkongsi satu bahagian.
     * Sebarang penulisan semula yang menyertai (`join`) `reports` kepada
     * `users` akan mengeluarkan satu baris per pasangan pengguna/aduan, jadi
     * kiraan akan digandakan dengan bilangan pengguna dalam bahagian itu —
     * tiga pengguna dan empat aduan akan memberi 12, bukan 4. Subkueri
     * berkorelasi wujud tepat untuk mengelak itu, jadi ia diuji secara
     * eksplisit: tepat satu baris, dan kiraan yang tidak digandakan.
     */
    public function test_several_users_in_one_bahagian_collapse_to_a_single_unmultiplied_row()
    {
        $bahagian = 'Bahagian Pengurusan Maklumat';

        // Tujuh pengguna dalam bahagian yang sama — enam di sini, satu lagi
        // ialah pengguna yang bertindak di bawah.
        for ($i = 0; $i < 6; $i++) {
            $this->userInBahagian($bahagian);
        }

        foreach ([2, 3, 4, 5] as $status) {
            $this->makeReport($bahagian, $status);
        }
        $this->makeReport($bahagian, self::DRAF);

        $response = $this->actingAs($this->managementUser($bahagian))->get(self::PATH);
        $response->assertOk();

        $laporans = $response->viewData('laporans');

        $rows = $laporans->filter(fn ($row) => $row->bahagian === $bahagian)->values();

        $this->assertCount(
            1,
            $rows,
            'Tujuh pengguna dalam satu bahagian mesti menjadi satu baris sahaja.'
        );
        $this->assertSame(
            4,
            (int) $rows[0]->jumlah,
            'Kiraan digandakan dengan bilangan pengguna — pertanyaan telah kembali kepada bentuk JOIN.'
        );
    }

    // ------------------------------------------------------------------
    // Penapis dan susunan
    // ------------------------------------------------------------------

    /**
     * Penapis `department` menghadkan hasil kepada satu baris, dengan kiraan
     * yang sama seperti tanpa penapis.
     */
    public function test_the_department_filter_narrows_to_a_single_row_with_the_same_count()
    {
        $this->userInBahagian('Bahagian A');
        $this->userInBahagian('Bahagian A');
        $this->userInBahagian('Bahagian B');

        $this->makeReport('Bahagian A', 2);
        $this->makeReport('Bahagian A', 5);
        $this->makeReport('Bahagian A', self::DRAF);
        $this->makeReport('Bahagian B', 2);

        $admin = $this->managementUser();

        $unfiltered = $this->actingAs($admin)->get(self::PATH);
        $unfiltered->assertOk();
        $expected = (int) $this->counts($unfiltered->viewData('laporans'))['Bahagian A'];
        $this->assertSame(2, $expected);

        $filtered = $this->actingAs($admin)->get(self::PATH.'?department=Bahagian+A');
        $filtered->assertOk();

        $laporans = $filtered->viewData('laporans');

        $this->assertCount(1, $laporans, 'Penapis bahagian mesti memberi tepat satu baris.');
        $this->assertSame('Bahagian A', $laporans[0]->bahagian);
        $this->assertSame($expected, (int) $laporans[0]->jumlah);

        // Senarai aduan di bawah jadual agregat ditapis bersama.
        $this->assertTrue(
            $filtered->viewData('reports')->every(fn ($report) => $report->bahagian_pengadu === 'Bahagian A'),
            'Senarai aduan mesti dihadkan kepada bahagian yang ditapis.'
        );
    }

    /**
     * Susunan menaik mengikut bahagian, dengan NULL dahulu — susunan yang
     * diberikan `order by users.ldap_bahagian asc` pada kedua-dua pemacu.
     */
    public function test_rows_are_ordered_null_first_then_ascending()
    {
        foreach ([null, 'Bahagian Zulu', 'Bahagian Alfa', 'Bahagian Mike'] as $bahagian) {
            $this->userInBahagian($bahagian);
        }

        $response = $this->actingAs($this->managementUser())->get(self::PATH);
        $response->assertOk();

        $order = $response->viewData('laporans')->pluck('bahagian')->all();

        $this->assertSame(
            [null, 'Bahagian Alfa', 'Bahagian Mike', 'Bahagian Zulu'],
            $order
        );
    }

    // ------------------------------------------------------------------
    // Endpoint API
    // ------------------------------------------------------------------

    /**
     * `laporanByDepartmentApi()` kini berkongsi pembina yang sama, jadi ia
     * mesti mengembalikan struktur respons yang sama seperti sebelum
     * penulisan semula: `success`, dan `laporan` sebagai senarai objek
     * `bahagian` / `jumlah` ditambah `url` yang dilekatkan pengawal.
     */
    public function test_the_api_endpoint_returns_the_same_structure_with_the_appended_url()
    {
        $this->userInBahagian('Bahagian A');
        $this->userInBahagian('Bahagian A');
        $this->userInBahagian('Bahagian B');

        $this->makeReport('Bahagian A', 2);
        $this->makeReport('Bahagian A', 5);
        $this->makeReport('Bahagian A', self::DRAF);
        $this->makeReport('Bahagian B', 2);

        $response = $this->actingAs($this->managementUser())
            ->postJson('/api/laporan/by-department');

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $laporan = $response->json('laporan');

        // Satu baris per bahagian: NULL (pengguna yang bertindak dan pengarang
        // aduan), Bahagian A, Bahagian B.
        $this->assertCount(3, $laporan);

        foreach ($laporan as $row) {
            $this->assertSame(
                ['bahagian', 'jumlah', 'url'],
                array_keys($row),
                'Struktur baris API telah berubah.'
            );
        }

        $byBahagian = collect($laporan)->keyBy(fn ($row) => $row['bahagian'] ?? '');

        $this->assertSame(2, $byBahagian['Bahagian A']['jumlah']);
        $this->assertSame(1, $byBahagian['Bahagian B']['jumlah']);
        $this->assertSame(0, $byBahagian['']['jumlah']);

        $this->assertSame(
            route('laporan.view-by-department', ['department' => 'Bahagian A']),
            $byBahagian['Bahagian A']['url']
        );
    }

    /**
     * `jumlah` ialah integer dalam JSON, bukan rentetan.
     *
     * Modal laporan menjumlahkan nilai ini dalam JavaScript. Rentetan akan
     * dicantum (`"2" + "1" === "21"`) dan bukan dijumlahkan, jadi jenis itu
     * sebahagian daripada kontrak dan bukan kebetulan pengekodan.
     */
    public function test_the_api_returns_jumlah_as_an_integer_so_javascript_can_sum_it()
    {
        $this->userInBahagian('Bahagian A');
        $this->userInBahagian('Bahagian B');

        $this->makeReport('Bahagian A', 2);
        $this->makeReport('Bahagian A', 5);
        $this->makeReport('Bahagian B', 2);

        $response = $this->actingAs($this->managementUser())
            ->postJson('/api/laporan/by-department');

        $response->assertOk();

        foreach ($response->json('laporan') as $row) {
            $this->assertIsInt(
                $row['jumlah'],
                'JS menjumlahkan `jumlah`; rentetan akan dicantum dan bukan dijumlahkan.'
            );
        }

        // Dan hasil tambahnya betul, iaitu apa yang JS kira.
        $this->assertSame(
            3,
            array_sum(array_column($response->json('laporan'), 'jumlah'))
        );
    }

    /**
     * Endpoint API berada di belakang middleware `auth`, jadi tetamu tidak
     * boleh mengambil data bahagian seluruh organisasi.
     */
    public function test_a_guest_cannot_call_the_department_api()
    {
        $this->makeReport('Bahagian A', 2);

        $this->postJson('/api/laporan/by-department')->assertUnauthorized();
    }
}
