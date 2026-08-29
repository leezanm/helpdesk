<div>
    {{-- Toolbar: search + primary action --}}
    <div class="list-toolbar">
        <div class="list-search">
            <i class="bi bi-search"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                   placeholder="Cari tiket atau subjek...">
        </div>
        <a class="btn btn-primary" href="{{ url('/aduan/create') }}">
            <i class="bi bi-plus-lg me-1"></i> Aduan Baru
        </a>
    </div>

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
                                @if ($sortField === 'ticket_no') <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></i> @endif
                            </th>
                            <th class="sortable" wire:click="sortBy('subject')">
                                Subjek
                                @if ($sortField === 'subject') <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></i> @endif
                            </th>
                            <th>Kategori</th>
                            @if ($canAdmin) <th>Pengadu</th> @endif
                            <th class="sortable" wire:click="sortBy('submitted_at')">
                                Tarikh
                                @if ($sortField === 'submitted_at') <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill"></i> @endif
                            </th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr wire:key="report-{{ $report->id }}">
                                <td class="text-muted">{{ $loop->iteration + ($reports->currentPage() - 1) * $reports->perPage() }}</td>
                                <td><span class="fw-semibold">{{ $report->ticket_no }}</span></td>
                                <td>{{ $report->subject }}</td>
                                <td>{{ $report->category->desc ?? '-' }}</td>
                                @if ($canAdmin)
                                    <td>
                                        <div class="fw-medium">{{ $report->reportBy->name ?? '-' }}</div>
                                        <small class="text-muted">{{ $report->reportBy->ldap_bahagian ?? '' }}</small>
                                    </td>
                                @endif
                                <td class="text-nowrap">{{ convertDateFormat($report->submitted_at, 'LL') }}</td>
                                <td>{!! getDuration($report) !!}</td>
                                <td><span class="{{ $report->status->class }}">{{ $report->status->desc }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('aduan.show', $report->id) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Lihat"><i class="bi bi-eye"></i></a>
                                    @if ($report->report_status_id == 1)
                                        <a href="{{ route('aduan.edit', $report->id) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini"><i class="bi bi-pencil"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canAdmin ? 9 : 8 }}">
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
