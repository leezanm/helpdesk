<div>
    {{--
        Ringkasan tempoh yang reaktif.
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 6.2)

        Blok ini berada dalam DOM komponen Livewire (bukan dalam kepala halaman
        statik `laporan/show_by_all.blade.php`) supaya ia dimorf semula pada
        setiap render apabila penapis tarikh atau Bahagian berubah
        (Requirement 1 kriteria 4).

        Tarikh yang dipaparkan ialah nilai yang sudah dibersihkan oleh render()
        komponen (`periodFrom`/`periodTo`), jadi tarikh kosong atau parameter URL yang
        tidak sah jatuh kepada julat lalai `mount()` tanpa ringkasan yang kosong
        (Requirement 1 kriteria 8). Nilai `dateFrom`/`dateTo` yang dimasukkan
        pengguna tidak disentuh di sini (Requirement 1 kriteria 9).
    --}}
    @php
        // Requirement 1 kriteria 5 dan 7: nama Bahagian dipendekkan kepada
        // 60 aksara, atau penanda liputan "Semua Bahagian" apabila tiada.
        // Pemendekan ini paparan sahaja — penapis sendiri tidak diubah.
        $metaScope = filled($bahagian) ? Str::limit($bahagian, 60) : 'Semua Bahagian';
    @endphp
    <div class="mb-3">
        <span class="report-meta {{ $invalidRange ? 'report-meta--warn' : '' }}">
            <i class="bi {{ $invalidRange ? 'bi-exclamation-triangle' : 'bi-calendar-range' }}"></i>
            <span>{{ convertDateFormat($periodFrom, 'LL') }} hingga {{ convertDateFormat($periodTo, 'LL') }}</span>
            <span aria-hidden="true">&middot;</span>
            <span>{{ $metaScope }}</span>
            @if ($invalidRange)
                <span aria-hidden="true">&middot;</span>
                <span>Julat tarikh tidak sah: Dari Tarikh lebih lewat daripada Hingga Tarikh.</span>
            @endif
        </span>
    </div>

    {{--
        Kad Penapis_Laporan.
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 8.2)

        $busy menyenaraikan setiap pencetus permintaan pada halaman ini —
        ketujuh penapis, butang Set Semula dan ketiga-tiga kaedah penomboran
        WithPagination. Senarai yang sama dihantar kepada prop
        `reset-busy-target` x-filter-bar (tugasan 8.1) supaya butang "Set
        Semula" menerima atribut `disabled` sebenar semasa permintaan dalam
        penerbangan (Requirement 7 kriteria 6), dan ia juga menjadi sasaran
        tindanan memuat bahagian lain (tugasan 12.1).

        Eksport CSV disasarkan secara berasingan (`wire:target="exportCsv"`)
        kerana ia bukan permintaan penapis: ia tidak mengubah Set_Penapis_Semasa
        dan tidak boleh menyahaktifkan butang Set Semula atau sebaliknya
        (Requirement 11 kriteria 13 hingga 15).
    --}}
    @php
        $busy = 'dateFrom,dateTo,bahagian,status,pegawai,rating,riskLevel,resetFilters,gotoPage,previousPage,nextPage';
    @endphp

    {{-- ===== Filters (all on one page) ===== --}}
    <x-filter-bar title="Penapis Laporan" :show-reset="true" :reset-busy-target="$busy">
        <x-slot:action>
            {{--
                Tiada butang "Jana Laporan": ketujuh penapis sudah reaktif
                (`wire:model.live`), jadi kepala kad membawa tepat dua butang —
                "Set Semula" daripada `show-reset` dan butang ini
                (Requirement 2 kriteria 12).
            --}}
            <button type="button" class="btn btn-sm btn-primary"
                    wire:click="exportCsv"
                    wire:loading.attr="disabled" wire:target="exportCsv">
                <span wire:loading wire:target="exportCsv"
                      class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                <i class="bi bi-filetype-csv me-1" wire:loading.remove wire:target="exportCsv"></i>
                Eksport CSV
            </button>
        </x-slot:action>

        {{--
            Kedua-dua kawalan tarikh mendapat penanda amaran sebaris semasa
            Julat_Tarikh_Tidak_Sah. Penanda ini paparan sahaja — nilai
            `dateFrom`/`dateTo` yang dimasukkan pengguna tidak dikosongkan dan
            tidak ditukar, dan laporan diteruskan render dengan nilai 0 serta
            keadaan kosong (Requirement 2 kriteria 10, Requirement 9 kriteria 12).
        --}}
        <x-filter-field label="Dari Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateFrom" class="form-control">
            @if ($invalidRange)
                <p class="text-danger small mt-1 mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Julat tarikh tidak sah.
                </p>
            @endif
        </x-filter-field>

        <x-filter-field label="Hingga Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateTo" class="form-control">
            @if ($invalidRange)
                <p class="text-danger small mt-1 mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Julat tarikh tidak sah.
                </p>
            @endif
        </x-filter-field>

        <x-filter-field label="Bahagian" col="col-12 col-md-3">
            <select wire:model.live="bahagian" class="form-select">
                <option value="">Semua Bahagian</option>
                @foreach ($bahagianOptions as $b)
                    <option value="{{ $b }}">{{ $b }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Status Tindakan" col="col-6 col-md-3">
            <select wire:model.live="status" class="form-select">
                <option value="">Semua Status</option>
                @foreach ($statusOptions as $s)
                    <option value="{{ $s->id }}">{{ $s->desc }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Pegawai Bertanggungjawab" col="col-12 col-md-5">
            <select wire:model.live="pegawai" class="form-select">
                <option value="">Semua Pegawai</option>
                @foreach ($pegawaiOptions as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Tahap Risiko" col="col-12 col-md-3">
            <select wire:model.live="riskLevel" class="form-select">
                <option value="">Semua Tahap Risiko</option>
                @foreach ($riskLevelOptions as $r)
                    <option value="{{ $r->id }}">{{ $r->label }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Rating / Maklum Balas" col="col-12 col-md-4">
            <select wire:model.live="rating" class="form-select">
                <option value="">Semua Rating</option>
                <option value="5">⭐⭐⭐⭐⭐ — Cemerlang</option>
                <option value="4">⭐⭐⭐⭐ — Baik</option>
                <option value="3">⭐⭐⭐ — Puas Hati</option>
                <option value="2">⭐⭐ — Kurang Berpuas Hati</option>
                <option value="1">⭐ — Lemah</option>
            </select>
        </x-filter-field>

        {{--
            Jalur mesej eksport ("Tiada data untuk dieksport."), dalam badan kad
            penapis supaya ia muncul betul-betul di bawah butang yang
            mencetuskannya (Requirement 11 kriteria 16 dan 17).

            `$exportNotice ?? null`: sifat tersebut ditambah pada komponen oleh
            tugasan 11.2, jadi pengawal ini menjaga view daripada gagal sebelum
            tugasan itu mendarat.
        --}}
        @if (filled($exportNotice ?? null))
            <div class="col-12">
                <div class="report-notice" role="status">
                    <i class="bi bi-info-circle"></i>
                    <span>{{ $exportNotice }}</span>
                </div>
            </div>
        @endif
    </x-filter-bar>

    {{--
        Kad_Statistik.
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 9.1)

        Lapan kad: tujuh status daripada `$statusCounts` (sudah disusun menaik
        mengikut `_report_statuses.id` oleh komponen, dan sudah lengkap kerana
        statusCounts() memetakan agregat ke atas senarai penuh
        `ReportStatuses::COUNTED`, jadi status tanpa aduan kekal hadir dengan
        total 0), ditambah satu kad "Jumlah" (Requirement 3 kriteria 1, 2, 9).

        Tiada bilangan, label status mahupun pemetaan warna dalam view: setiap
        satu daripada `desc`, `total`, `tone` dan `icon` datang daripada baris
        `$statusCounts`, dan `tone` itu sendiri diterbitkan oleh komponen
        daripada `_report_statuses.class` — sumber yang sama dengan badge status
        dalam Jadual_Aduan (Requirement 3 kriteria 3, 6, 11).

        `.stat-grid` ialah CSS grid (bukan grid Bootstrap), jadi kad ialah anak
        langsung tanpa pembalut `.col-*`.

        Kad bukan kawalan: tiada `wire:click`, `href`, `<a>` atau `<button>` di
        sini atau dalam `x-laporan.stat-tile`, jadi klik pada kad tidak mengubah
        Set_Penapis_Semasa atau kandungan halaman (Requirement 3 kriteria 13).
    --}}
    {{--
        Tindanan memuat (1 daripada 3).
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 12.1)

        Tindanan, bukan penggantian: kad di bawah tabir ini kekal dirender
        dengan nilai hasil terakhir sepanjang permintaan, kerana Livewire hanya
        memorf DOM selepas respons tiba. Jadi tiada nilai kosong dan tiada
        keadaan kosong palsu semasa memuat (Requirement 7 kriteria 3).

        Ketiga-tiga tabir berkongsi `$busy` yang sama, jadi ia muncul dan hilang
        serentak apabila Livewire menandakan permintaan bermula dan selesai
        (Requirement 7 kriteria 1 dan 4). Tiada pengendalian ralat atau tamat
        masa ditambah: permintaan yang gagal dikendalikan oleh Livewire seperti
        biasa (Requirement 7 kriteria 7).

        `mb-3` dipindahkan ke pembalut supaya jidar berada di luar kotak tabir
        (`.report-busy__veil` ialah `inset: 0`), jadi tabir menutup tepat
        kandungan bahagian ini sahaja.
    --}}
    <div class="report-busy mb-3">
        <div class="report-busy__veil" wire:loading.flex wire:target="{{ $busy }}">
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </div>

        <div class="stat-grid">
            @foreach ($statusCounts as $item)
                <x-laporan.stat-tile
                    wire:key="stat-{{ $item['id'] }}"
                    :label="$item['desc']"
                    :value="$item['total']"
                    :tone="$item['tone']"
                    :icon="$item['icon']" />
            @endforeach

            {{-- Kad kelapan: gradien --c-primary datang daripada `.status-tile--total`
                 yang dihidupkan oleh prop `total` (Requirement 3 kriteria 7). --}}
            <x-laporan.stat-tile
                wire:key="stat-jumlah"
                label="Jumlah"
                :value="$grandTotal"
                icon="bi-collection"
                :total="true" />
        </div>
    </div>

    {{--
        Baris Carta_Status + Carta_Tren.
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 9.2)

        `col-12 col-lg-6`: dua lajur sama lebar dari 992px ke atas (ambang `lg`
        Bootstrap), satu lajur bertindan pada 991px dan ke bawah
        (Requirement 8 kriteria 5, 6 dan 7). `.panel` membawa `height: 100%`,
        jadi kedua-dua kad kekal sama tinggi walaupun tajuknya berbeza panjang.

        Ketinggian bekas carta (300px, 260px pada <=991px) datang daripada
        `.chart-box` dalam `resources/css/app.css` dan dikongsi oleh ketiga-tiga
        keadaan — carta dilukis, keadaan kosong dan fallback — jadi susun atur
        tidak beranjak (Requirement 4 kriteria 4, Requirement 7 kriteria 5).
    --}}
    @php
        // Keadaan kosong carta (tugasan 12.2 mengesahkan ketiga-tiga laluan
        // masuk ke sini: julat tarikh terbalik, julat sah tanpa aduan sepadan,
        // dan id penapis yang tidak wujud — kesemuanya tiba sebagai jumlah
        // sifar, bukan sebagai pengecualian; lihat
        // tests/Feature/Laporan/LaporanEmptyStateTest.php).
        //
        // Keadaan kosong dikira di pelayan supaya `x-laporan.chart-box` boleh
        // meninggalkan elemen sasaran carta keluar daripada DOM sepenuhnya;
        // skrip carta (tugasan 9.3) kemudian keluar dengan senyap.
        //
        // Donat: tiada jumlah bermakna tiada segmen (Requirement 4 kriteria 6).
        $donutEmpty = $donut['total'] === 0;

        // Tren: DUA keadaan berbeza menghasilkan keadaan kosong yang sama.
        //  - `labels`/`values` kosong — paksi itu sendiri tiada, iaitu apa yang
        //    dipulangkan oleh trendData() semasa Julat_Tarikh_Tidak_Sah
        //    (Requirement 5 kriteria 11).
        //  - paksi penuh tetapi `total` sifar — setiap baldi bernilai 0 kerana
        //    tiada Aduan_Dikira dalam tempoh. Tanpa pemeriksaan ini carta akan
        //    melukis barisan bar sifar, sedangkan Requirement 5 kriteria 9
        //    menghendaki kawasan carta disembunyikan dan mesej keadaan kosong
        //    dipaparkan.
        $trendEmpty = empty($trend['values']) || $trend['total'] === 0;
    @endphp

    {{--
        Tindanan memuat (2 daripada 3): baris Carta_Status + Carta_Tren.
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 12.1)

        Satu tabir menutup kedua-dua kad carta, kerana Requirement 7 kriteria 1
        menghitung bahagian carta sebagai satu bahagian.

        Ketinggian tidak beranjak semasa memuat: tabir diposisikan secara mutlak
        (`position: absolute; inset: 0`), jadi ia tidak mengambil ruang dalam
        aliran, dan ketinggian bekas carta kekal ditentukan oleh `.chart-box`
        (Requirement 7 kriteria 5).
    --}}
    <div class="report-busy mb-3">
        <div class="report-busy__veil" wire:loading.flex wire:target="{{ $busy }}">
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="panel">
                    <div class="panel__head">
                        <div>
                            <h6 class="panel__title">Agihan Status Tindakan</h6>
                        </div>
                    </div>
                    <div class="panel__body">
                        {{--
                            Donat + Legenda_Carta_Status.
                            Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 15.2)

                            `.chart-split` meletakkan legenda di sebelah donat pada
                            >=992px dan di bawahnya pada <=991px (Requirement 4
                            kriteria 10 dan 12). Ia bekas susun atur sahaja: ketinggian
                            tetap carta kekal diisytiharkan sekali, pada `.chart-box`
                            (Requirement 4 kriteria 14).

                            Baris legenda dibina oleh skrip carta daripada pemegang
                            data JSON yang sama, jadi legenda tidak boleh menyimpang
                            daripada segmen donat. Dalam keadaan kosong bekas legenda
                            tidak dirender sama sekali — tiada segmen bermakna tiada
                            legenda (Requirement 4 kriteria 6).
                        --}}
                        <div class="chart-split">
                            <x-laporan.chart-box id="laporan-status-donut" :empty="$donutEmpty" />

                            @unless ($donutEmpty)
                                {{--
                                    `wire:ignore`: kandungan bekas ini dibina oleh skrip
                                    carta, jadi morph Livewire tidak perlu mengosongkannya
                                    pada setiap render — baris legenda terdahulu kekal
                                    kelihatan sehingga `laporan-charts-updated` melukisnya
                                    semula, tanpa kilatan kosong di antaranya.
                                --}}
                                <div id="laporan-status-legend" class="chart-legend" wire:ignore></div>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="panel">
                    <div class="panel__head">
                        <div>
                            <h6 class="panel__title">Tren Aduan</h6>
                        </div>
                    </div>
                    <div class="panel__body">
                        <x-laporan.chart-box id="laporan-trend-bar" :empty="$trendEmpty" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Results ===== --}}
    {{--
        Tindanan memuat (3 daripada 3): kad Jadual_Aduan.
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 12.1)

        `$busy` merangkumi `gotoPage`, `previousPage` dan `nextPage`, jadi
        pautan penomboran juga mencetuskan ketiga-tiga tabir, bukan tabir ini
        sahaja (Requirement 7 kriteria 1).

        Baris hasil terakhir kekal di bawah tabir sepanjang permintaan, jadi
        pengguna tidak melihat jadual kosong mahupun baris keadaan kosong
        "Tiada aduan sepadan dengan penapis." semasa menukar penapis atau
        halaman (Requirement 7 kriteria 3).
    --}}
    <div class="report-busy">
        <div class="report-busy__veil" wire:loading.flex wire:target="{{ $busy }}">
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        </div>

        {{--
            Jadual_Aduan.
            Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 9.4)

            `.table-responsive` mengehadkan penatalan mendatar kepada bekas
            jadual sahaja, jadi sepuluh lajur pada 575px dan ke bawah tidak
            menyebabkan badan halaman melimpah (Requirement 6 kriteria 15).

            `.table-hover` memberi sorotan baris: `.table-hover > tbody > tr:hover > *`
            dalam `app.css` menetapkan latar kepada `--c-primary-050` dan
            mengembalikannya apabila penuding keluar (Requirement 6 kriteria 8).

            `.table-head--primary` pada <thead> (bukan pada <table>): kelas itu
            ditulis sebagai `.table > thead.table-head--primary`, jadi ia mesti
            berada pada elemen thead untuk mengatasi latar `.table > thead`
            sedia ada (Requirement 6 kriteria 4).
        --}}
        <div class="card data-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-head--primary">
                            <tr>
                                {{-- Sepuluh lajur, kiri ke kanan; lajur pertama
                                     "Bil." menggantikan label "#" sedia ada
                                     (Requirement 6 kriteria 1). --}}
                                <th class="text-center">Bil.</th>
                                <th>Tiket</th>
                                <th>Subjek</th>
                                <th>Bahagian</th>
                                <th>Kategori</th>
                                <th>Risiko</th>
                                <th>Tarikh</th>
                                <th>Status</th>
                                <th class="text-center">Rating</th>
                                <th class="text-end">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports as $report)
                                <tr wire:key="lap-{{ $report->id }}">
                                    {{-- Bil. berjalan merentas halaman penomboran:
                                         formula sedia ada menambah offset halaman
                                         kepada $loop->iteration, jadi halaman 2
                                         bermula pada 21 (Requirement 6 kriteria 2). --}}
                                    <td class="text-center">{{ $loop->iteration + ($reports->currentPage() - 1) * $reports->perPage() }}</td>

                                    {{-- Satu-satunya sel berat fon tinggi pada baris:
                                         `fw-semibold` ialah 600, manakala sel lain
                                         kekal pada berat badan 400 kerana tiada kelas
                                         berat dikenakan (Requirement 6 kriteria 5). --}}
                                    <td class="fw-semibold text-nowrap">{{ $report->ticket_no }}</td>

                                    {{-- Subjek dipendekkan kepada 40 aksara seperti
                                         sebelum ini, dengan teks penuh sebagai tooltip
                                         pada sel (Requirement 6 kriteria 12). --}}
                                    <td title="{{ $report->subject }}">{{ Str::limit($report->subject, 40) }}</td>

                                    {{-- Empat medan yang boleh kosong — Bahagian,
                                         Kategori, Risiko dan Rating — memaparkan
                                         penanda `.cell-muted` dan bukan sel kosong
                                         (Requirement 6 kriteria 13). --}}
                                    <td>
                                        @if (filled($report->bahagian_pengadu))
                                            {{ $report->bahagian_pengadu }}
                                        @else
                                            <span class="cell-muted">&mdash;</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (filled($report->category?->desc))
                                            {{ $report->category->desc }}
                                        @else
                                            <span class="cell-muted">&mdash;</span>
                                        @endif
                                    </td>

                                    {{-- Badge risiko mengambil kelasnya daripada
                                         `_risk_levels.class`, jadi warna tahap risiko
                                         ada satu sumber sahaja di pangkalan data
                                         (Requirement 6 kriteria 7). --}}
                                    <td class="text-nowrap">
                                        @if ($report->riskLevel)
                                            <span class="{{ $report->riskLevel->class }}"
                                                  title="{{ $report->riskLevel->label }}">Tahap {{ $report->riskLevel->level }}</span>
                                        @else
                                            <span class="cell-muted">&mdash;</span>
                                        @endif
                                    </td>

                                    {{-- Tarikh melalui pembantu sedia ada, pada satu
                                         baris (`text-nowrap`) supaya format panjang
                                         Bahasa Melayu tidak terpisah
                                         (Requirement 6 kriteria 3). --}}
                                    <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>

                                    {{-- Badge status daripada `_report_statuses.class`
                                         — lajur yang sama yang menerbitkan nada kad
                                         statistik, jadi kedua-duanya tidak boleh
                                         berbeza (Requirement 6 kriteria 6). --}}
                                    <td class="text-nowrap">
                                        <span class="{{ $report->status->class }}">{{ $report->status->desc }}</span>
                                    </td>

                                    <td class="text-center text-nowrap">
                                        @if ($report->rating)
                                            {{-- Maklum balas penuh menjadi tooltip, jadi
                                                 sel kekal satu baris dan lajur Rating
                                                 tidak melebar. --}}
                                            <span class="small" title="{{ $report->feedback ?? '' }}">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i class="bi bi-star{{ $i <= $report->rating ? '-fill' : '' }}"
                                                       style="color: var(--c-butter)"></i>
                                                @endfor
                                            </span>
                                        @else
                                            <span class="cell-muted">&mdash;</span>
                                        @endif
                                    </td>

                                    {{-- Tindakan dijajarkan ke kanan; laluan
                                         `aduan.show` tidak berubah
                                         (Requirement 6 kriteria 9 dan 11). --}}
                                    <td class="text-end">
                                        <a href="{{ route('aduan.show', $report) }}"
                                           class="btn btn-sm btn-outline-primary btn-icon" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                {{-- Keadaan kosong merentas kesemua sepuluh lajur
                                     (Requirement 6 kriteria 14). --}}
                                <tr>
                                    <td colspan="10">
                                        <div class="empty-state">
                                            <i class="bi bi-search"></i>
                                            <span>Tiada aduan sepadan dengan penapis.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{--
                    Penomboran Livewire sedia ada, 20 rekod per halaman dan
                    susunan `submitted_at` menurun daripada `listQuery()`
                    (Requirement 6 kriteria 10). `hasPages()` bernilai false
                    bagi set hasil kosong, jadi baris keadaan kosong di atas
                    tidak pernah muncul bersama pautan penomboran
                    (Requirement 6 kriteria 14).
                --}}
                @if ($reports->hasPages())
                    <div class="px-2 pt-3">{{ $reports->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    {{--
        Pemegang data carta.
        Spec: .kiro/specs/laporan-keseluruhan-ui-refresh (tugasan 9.2)

        Corak yang sama seperti `#dashboard-chart-data` dalam
        `livewire/dashboard/dashboard.blade.php`: atribut `data-*` dengan
        `@json(...)` di dalam DOM komponen Livewire, jadi komponen menulis JSON
        yang segar ke sini pada SETIAP render.

        Ini satu-satunya pengangkut data carta (Requirement 5 kriteria 10).
        `render()` memancarkan `laporan-charts-updated` tanpa payload (tugasan
        4.4); skrip carta (tugasan 9.3) membaca semula pemegang ini pada isyarat
        tersebut dan melukis semula. Payload acara akan menjadi sumber kedua
        yang boleh bercanggah dengan pemegang ini, jadi sengaja tiada.
    --}}
    <div id="laporan-chart-data"
         data-status='@json($donut)'
         data-trend='@json($trend)'
         hidden></div>
</div>
