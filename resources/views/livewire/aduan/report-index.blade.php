<div>
    <x-filter-bar title="Carian & Penapis Aduan"
                  :show-reset="$search !== '' || $bahagian !== '' || $categoryId !== '' || $officerId !== '' || $statusId !== ''">
        <x-slot:action>
            <a class="btn btn-sm btn-primary" href="{{ url('/aduan/create') }}">
                <i class="bi bi-plus-lg me-1"></i> Aduan Baru
            </a>
        </x-slot:action>

        <x-filter-field label="Carian" col="col-12 col-md-4">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                       placeholder="Cari tiket atau subjek...">
            </div>
        </x-filter-field>

        <x-filter-field label="Bahagian" col="col-6 col-md-4">
            <select wire:model.live="bahagian" class="form-select">
                <option value="">Semua Bahagian</option>
                @foreach ($bahagianOptions as $b)
                    <option value="{{ $b }}">{{ $b }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Kategori" col="col-6 col-md-4">
            <select wire:model.live="categoryId" class="form-select">
                <option value="">Semua Kategori</option>
                @foreach ($categoryOptions as $c)
                    <option value="{{ $c->id }}">{{ $c->desc }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Pegawai Bertanggungjawab" col="col-12 col-md-6">
            <select wire:model.live="officerId" class="form-select">
                <option value="">Semua Pegawai Bertanggungjawab</option>
                @foreach ($officerOptions as $o)
                    <option value="{{ $o->id }}">{{ $o->name }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Status" col="col-12 col-md-6">
            <select wire:model.live="statusId" class="form-select">
                <option value="">Semua Status</option>
                @foreach ($statusOptions as $s)
                    <option value="{{ $s->id }}">{{ $s->desc }}</option>
                @endforeach
            </select>
        </x-filter-field>
    </x-filter-bar>

    {{-- Data card --}}
    <div class="card data-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th class="sortable" wire:click="sortBy('ticket_no')">
                                Tiket
                                @if ($sortField === 'ticket_no')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></i>
                                @endif
                            </th>
                            <th class="sortable" wire:click="sortBy('subject')">
                                Subjek
                                @if ($sortField === 'subject')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></i>
                                @endif
                            </th>
                            <th>Kategori</th>
                            @if ($showPengadu)
                                <th>Pengadu</th>
                            @endif
                            @if ($showOfficer)
                                <th>Pegawai Bertanggungjawab</th>
                            @endif
                            <th class="sortable" wire:click="sortBy('submitted_at')">
                                Tarikh
                                @if ($sortField === 'submitted_at')
                                    <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></i>
                                @endif
                            </th>
                             @if ($showOfficer)
                            <th>Durasi</th>
                            @endif
                            <th>Status</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr wire:key="report-{{ $report->id }}">
                                <td class="text-muted">
                                    {{ $loop->iteration + ($reports->currentPage() - 1) * $reports->perPage() }}</td>
                                <td><span class="fw-semibold">{{ $report->ticket_no }}</span></td>
                                <td>{{ $report->subject }}</td>
                                <td>{{ $report->category->desc ?? '-' }}</td>
                                @if ($showPengadu)
                                    <td><x-pengadu-cell :report="$report" /></td>
                                @endif
                                @if ($showOfficer)
                                    <td>{{ $assignedOfficers[$report->id] ?? '—' }}</td>
                                @endif

                                <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                @if ($showOfficer)
                                <td>{!! getDuration($report) !!}</td>
                                @endif
                                <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('aduan.show', $report) }}"
                                        class="btn btn-sm btn-outline-primary btn-icon" title="Lihat"><i
                                            class="bi bi-eye"></i></a>
                                    @if ($report->report_status_id == 1)
                                        <a href="{{ route('aduan.edit', $report) }}"
                                            class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini"><i
                                                class="bi bi-pencil"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 8 + ($showPengadu ? 1 : 0) + ($showOfficer ? 1 : 0) }}">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        Tiada aduan dijumpai.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reports->hasPages())
                <div class="px-2 pt-3">{{ $reports->links() }}</div>
            @endif
        </div>
    </div>
</div>
