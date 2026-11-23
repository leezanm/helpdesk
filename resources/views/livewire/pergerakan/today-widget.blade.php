{{-- Gaya mengikut kad dashboard yang ada: .panel + .kpi-strip, supaya widget
     ini kelihatan sebahagian daripada dashboard dan bukan tampalan.

     wire:poll: status bergantung pada masa semasa, jadi dashboard yang dibiar
     terbuka tidak boleh kekal memaparkan "dalam mesyuarat" selepas mesyuarat
     itu tamat. --}}
<div class="panel panel--block mb-4" wire:poll.60s>
    <div class="panel__head">
        <div>
            <span class="panel__hint d-block">{{ $dateLabel }} &middot; {{ $timeLabel }}</span>
            <h6 class="panel__title">
                Ketersediaan Pegawai
                @if ($unit)
                    <span class="fw-normal text-muted">&middot; {{ $unit->short_name ?: $unit->name }}</span>
                @endif
            </h6>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($holiday)
                <span class="badge bg-warning text-dark">{{ $holiday }}</span>
            @endif

            @if ($units->count() > 1)
                <select wire:model.live="unitId" class="form-select form-select-sm" style="width:auto">
                    @foreach ($units as $u)
                        <option value="{{ $u->id }}">{{ $u->short_name ?: $u->name }}</option>
                    @endforeach
                </select>
            @endif

            <a href="{{ route('pergerakan.calendar') }}" class="btn btn-sm btn-outline-secondary text-nowrap">
                <i class="bi bi-calendar3 me-1"></i> Lihat Jadual Bulanan
            </a>
        </div>
    </div>

    <div class="panel__body">
        @if ($officerCount === 0)
            <p class="text-muted small mb-0">
                Belum ada pegawai dalam senarai pemantauan.
                @can('pergerakan.manageRoster')
                    <a href="{{ route('pergerakan.roster') }}">Tambah pegawai dipantau</a>.
                @endcan
            </p>
        @else
            {{-- Empat kategori, dinilai pada masa semasa. --}}
            <div class="kpi-strip kpi-strip--availability mb-3">
                <div class="kpi kpi--available">
                    <div class="kpi__top">
                        <span class="kpi__ic stat-icon--success"><i class="bi bi-person-check"></i></span>
                        <span class="kpi__label">Tersedia</span>
                    </div>
                    <div class="kpi__value">{{ $availableCount }}</div>
                    <div class="kpi__foot">Di pejabat, boleh diurus kerja</div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__ic stat-icon--warning"><i class="bi bi-people"></i></span>
                        <span class="kpi__label">Tidak Tersedia</span>
                    </div>
                    <div class="kpi__value">{{ $busyInsideCount }}</div>
                    <div class="kpi__foot">Dalam program di pejabat sekarang</div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__ic stat-icon--primary"><i class="bi bi-geo-alt"></i></span>
                        <span class="kpi__label">Luar Pejabat</span>
                    </div>
                    <div class="kpi__value">{{ $outsideCount }}</div>
                    <div class="kpi__foot">Bertugas di luar sekarang</div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__ic stat-icon--accent"><i class="bi bi-cup-hot"></i></span>
                        <span class="kpi__label">Bercuti</span>
                    </div>
                    <div class="kpi__value">{{ $onLeaveCount }}</div>
                    <div class="kpi__foot">Cuti rehat / sakit</div>
                </div>
            </div>

            {{-- Hanya pegawai yang tersedia disenaraikan. --}}
            @if ($availableCount > 0)
                <div class="availability-list">
                    @foreach ($available as $row)
                        <div class="availability-item" wire:key="available-{{ $row['officer']->id }}">
                            <span class="availability-dot"></span>
                            <span class="availability-item__name">{{ $row['officer']->display_name }}</span>
                            @if ($row['freeUntil'])
                                {{-- Komitmen kemudian pada hari ini: tingkap masa yang terbuka. --}}
                                <span class="availability-item__until" title="Ada program bermula {{ $row['freeUntil'] }}">
                                    hingga {{ $row['freeUntil'] }}
                                </span>
                            @else
                                <span class="availability-item__status">Tersedia</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted small mb-0">
                    Tiada pegawai tersedia sekarang — semuanya dalam program, bertugas luar, atau bercuti.
                </p>
            @endif
        @endif
    </div>
</div>
