<div>
    @if (session('movement-message'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i> {{ session('movement-message') }}</div>
    @endif

    <div class="card data-card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary btn-icon" wire:click="previousMonth" title="Bulan sebelum">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <h5 class="card-title mb-0 text-nowrap">{{ $monthLabel }}</h5>
                    <button class="btn btn-sm btn-outline-secondary btn-icon" wire:click="nextMonth" title="Bulan seterusnya">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" wire:click="goToToday">Bulan ini</button>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @if ($units->count() > 1)
                        <select wire:model.live="unitId" class="form-select form-select-sm" style="width:auto">
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}">{{ $u->short_name ?: $u->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($unit)
                        {{-- Cetak dan eksport mengikut bulan + unit yang sedang dipapar. --}}
                        <a class="btn btn-sm btn-outline-secondary text-nowrap" target="_blank"
                           href="{{ route('pergerakan.print', ['unit' => $unit->id, 'year' => $year, 'month' => $month]) }}">
                            <i class="bi bi-printer"></i> Cetak
                        </a>
                        <button class="btn btn-sm btn-outline-secondary text-nowrap" wire:click="exportCsv">
                            <i class="bi bi-filetype-csv"></i> CSV
                        </button>
                    @endif
                </div>
            </div>

            @if (! $unit)
                <div class="alert alert-warning mb-0">
                    Tiada unit pemantauan aktif. Hubungi pentadbir helpdesk untuk menyediakan unit dan
                    senarai pegawai dipantau.
                </div>
            @elseif (count($rows) === 0)
                <div class="alert alert-info mb-0">
                    Belum ada pegawai dalam senarai pemantauan untuk {{ $unit->name }}.
                    @can('pergerakan.manageRoster')
                        <a href="{{ route('pergerakan.roster') }}">Tambah pegawai dipantau</a>.
                    @endcan
                </div>
            @else
                <p class="small text-muted mb-2">
                    Jadual Pergerakan {{ $unit->name }}@if ($unit->short_name), {{ $unit->short_name }}@endif
                </p>

                {{-- Grid bulanan (desktop).

                     Panel tatalnya sendiri (dua paksi) dan bukan .table-responsive:
                     tajuk hanya boleh dibekukan menegak jika bekas yang menatal
                     ialah bekas ini, bukan halaman.

                     Dua baris tajuk sahaja di skrin (nama hari + tarikh). Baris
                     "MINGGU PERTAMA" dikekalkan pada paparan cetak yang menyalin
                     bentuk jadual rasmi; di skrin ia hanya mengelirukan kerana
                     label minggu menatal keluar daripada pandangan. --}}
                <div class="movement-grid-scroll d-none d-md-block"
                     x-data="{
                        focusToday() {
                            const cell = $el.querySelector('[data-today]');

                            if (! cell) {
                                // Bulan lain: mula dari 1 haribulan.
                                $el.scrollLeft = 0;

                                return;
                            }

                            // Tolak lebar lajur nama yang dibekukan, jika tidak
                            // lajur hari ini berakhir tersembunyi di bawahnya.
                            const frozen = $el.querySelector('.movement-grid-name');
                            const offset = frozen ? frozen.getBoundingClientRect().width : 0;

                            $el.scrollLeft += cell.getBoundingClientRect().left
                                - $el.getBoundingClientRect().left - offset - 8;
                        }
                     }"
                     x-init="$nextTick(() => focusToday())"
                     {{-- Komponen menghantar peristiwa ini setiap kali bulan bertukar;
                          x-init tidak berjalan semula kerana Livewire hanya menambal DOM. --}}
                     x-on:pergerakan-month-changed.window="$nextTick(() => focusToday())">
                    <table class="table table-bordered table-sm align-middle mb-0 movement-grid">
                        <thead>
                            <tr>
                                <th rowspan="2" style="min-width:200px" class="align-middle movement-grid-name">Nama Pegawai</th>
                                @foreach ($days as $day)
                                    <th class="text-center small {{ $day['is_weekend'] ? 'is-weekend' : '' }} {{ $day['holiday'] ? 'is-holiday' : '' }}"
                                        style="min-width:150px">
                                        {{ $day['name'] }}
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($days as $day)
                                    {{-- data-today ialah sasaran tatal; ia membawa tarikhnya
                                         supaya penanda itu jelas dalam HTML dan dalam ujian. --}}
                                    <th @if ($day['is_today']) data-today="{{ $day['key'] }}" @endif
                                        class="text-center small movement-grid-date {{ $day['is_weekend'] ? 'is-weekend' : '' }} {{ $day['holiday'] ? 'is-holiday' : '' }} {{ $day['is_today'] ? 'is-today' : '' }}">
                                        {{ $day['date']->format('j M') }}
                                        @if ($day['holiday'])
                                            <span class="d-block text-truncate fw-normal" title="{{ $day['holiday'] }}">{{ $day['holiday'] }}</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="row-{{ $row['officer']->id }}">
                                    <th class="align-middle small movement-grid-name">{{ $row['officer']->display_name }}</th>
                                    @foreach ($days as $day)
                                        @php $entries = $row['cells'][$day['key']]; @endphp
                                        <td class="align-top {{ $day['is_weekend'] ? 'is-weekend' : '' }} {{ $day['holiday'] ? 'is-holiday' : '' }}"
                                            wire:key="cell-{{ $row['officer']->id }}-{{ $day['key'] }}">
                                            @foreach ($entries as $movement)
                                                <a href="#" class="d-block text-decoration-none mb-1"
                                                   wire:click.prevent="showDetails({{ $movement->id }})"
                                                   title="{{ $movement->title }}">
                                                    <span class="badge {{ $movement->type->class }} text-wrap text-start">
                                                        {{ $movement->title }}
                                                        @if ($movement->isMultiDay())
                                                            <i class="bi bi-arrow-left-right ms-1" title="Aktiviti berbilang hari"></i>
                                                        @endif
                                                    </span>
                                                    @if ($movement->start_time)
                                                        <span class="d-block small text-muted">{{ substr($movement->start_time, 0, 5) }}@if ($movement->end_time) – {{ substr($movement->end_time, 0, 5) }}@endif</span>
                                                    @endif
                                                    @if ($movement->location)
                                                        <span class="d-block small text-muted text-truncate">{{ $movement->location }}</span>
                                                    @endif
                                                </a>
                                            @endforeach

                                            @can('pergerakan.create')
                                                <a href="#" class="small text-muted text-decoration-none"
                                                   wire:click.prevent="addEntry('{{ $day['key'] }}', {{ $row['officer']->id }})"
                                                   title="Tambah pergerakan"><i class="bi bi-plus"></i></a>
                                            @endcan
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pandangan agenda (telefon) — data yang sama, disusun ikut hari --}}
                <div class="d-md-none">
                    @forelse ($agenda as $entry)
                        <div class="border rounded p-2 mb-2 {{ $entry['day']['is_weekend'] ? 'bg-light' : '' }}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold small">
                                    {{ $entry['day']['date']->format('j M') }} · {{ $entry['day']['name'] }}
                                </span>
                                @if ($entry['day']['holiday'])
                                    <span class="badge bg-warning text-dark">{{ $entry['day']['holiday'] }}</span>
                                @endif
                            </div>

                            @foreach ($entry['entries'] as $item)
                                <a href="#" class="d-block text-decoration-none border-top pt-2 mt-2"
                                   wire:click.prevent="showDetails({{ $item['movement']->id }})">
                                    <span class="small text-muted d-block">{{ $item['officer']->display_name }}</span>
                                    <span class="badge {{ $item['movement']->type->class }} text-wrap text-start">{{ $item['movement']->title }}</span>
                                    @if ($item['movement']->start_time)
                                        <span class="d-block small text-muted">{{ substr($item['movement']->start_time, 0, 5) }}@if ($item['movement']->end_time) – {{ substr($item['movement']->end_time, 0, 5) }}@endif</span>
                                    @endif
                                    @if ($item['movement']->location)
                                        <span class="d-block small text-muted">{{ $item['movement']->location }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">Tiada pergerakan direkod untuk {{ $monthLabel }}.</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    {{-- Butiran entri --}}
    @if ($detail)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Butiran Pergerakan</h5>
                        <button type="button" class="btn-close" wire:click="closeDetails" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="row mb-0 small">
                            <dt class="col-4">Pegawai</dt>
                            <dd class="col-8">{{ $detail->officer->display_name }}</dd>

                            <dt class="col-4">Jenis</dt>
                            <dd class="col-8"><span class="badge {{ $detail->type->class }}">{{ $detail->type->name }}</span></dd>

                            <dt class="col-4">Tajuk</dt>
                            <dd class="col-8">{{ $detail->title }}</dd>

                            <dt class="col-4">Tarikh</dt>
                            <dd class="col-8">
                                {{ $detail->start_date->format('d/m/Y') }}
                                @if ($detail->isMultiDay()) – {{ $detail->end_date->format('d/m/Y') }} @endif
                            </dd>

                            <dt class="col-4">Masa</dt>
                            <dd class="col-8">
                                @if ($detail->start_time)
                                    {{ substr($detail->start_time, 0, 5) }}@if ($detail->end_time) – {{ substr($detail->end_time, 0, 5) }}@endif
                                @else
                                    Sepanjang hari
                                @endif
                            </dd>

                            <dt class="col-4">Lokasi</dt>
                            <dd class="col-8">
                                @unless ($detail->type->is_leave)
                                    <span class="badge {{ $detail->is_outside ? 'bg-primary' : 'bg-light text-dark' }}">
                                        {{ $detail->is_outside ? 'Luar pejabat' : 'Dalam pejabat' }}
                                    </span>
                                @endunless
                                {{ $detail->location ?: '' }}
                                @if (! $detail->location && $detail->type->is_leave) — @endif
                            </dd>

                            <dt class="col-4">Catatan</dt>
                            <dd class="col-8">{{ $detail->remarks ?: '—' }}</dd>

                            @can('pergerakan.manageAny')
                                <dt class="col-4">Dicatat oleh</dt>
                                <dd class="col-8">{{ $detail->createdBy?->name ?? '—' }}</dd>

                                @if ($detail->updatedBy)
                                    <dt class="col-4">Dikemaskini oleh</dt>
                                    <dd class="col-8">{{ $detail->updatedBy->name }}</dd>
                                @endif
                            @endcan
                        </dl>

                        @cannot('update', $detail)
                            <p class="small text-muted mb-0 mt-3">
                                <i class="bi bi-lock"></i> Tarikh entri ini sudah sampai — rujuk pentadbir helpdesk untuk kemaskini.
                            </p>
                        @endcannot
                    </div>
                    <div class="modal-footer">
                        @can('delete', $detail)
                            <a href="#" class="btn btn-outline-danger me-auto"
                               wire:click.prevent="delete({{ $detail->id }})"
                               wire:confirm="Hapus pergerakan &quot;{{ $detail->title }}&quot;?">Hapus</a>
                        @endcan
                        <button class="btn btn-secondary" type="button" wire:click="closeDetails">Tutup</button>
                        @can('update', $detail)
                            <button class="btn btn-success" type="button" wire:click="edit({{ $detail->id }})">Kemaskini</button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @livewire('pergerakan.movement-form')
</div>
