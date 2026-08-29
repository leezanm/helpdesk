<div>
    <x-filter-bar title="Carian & Penapis Log"
                  :show-reset="$search !== '' || $userId !== '' || $dateFrom !== '' || $dateTo !== '' || $method !== ''">
        <x-filter-field label="Carian" col="col-12 col-md-4">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                       placeholder="Cari URL, IP, atau nama pengguna...">
            </div>
        </x-filter-field>

        <x-filter-field label="Pelaku" col="col-12 col-md-4">
            <select wire:model.live="userId" class="form-select">
                <option value="">Semua Pelaku</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </x-filter-field>

        <x-filter-field label="Method" col="col-12 col-md-4">
            <select wire:model.live="method" class="form-select">
                <option value="">Semua Method</option>
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="PATCH">PATCH</option>
                <option value="DELETE">DELETE</option>
            </select>
        </x-filter-field>

        <x-filter-field label="Dari Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateFrom" class="form-control">
        </x-filter-field>

        <x-filter-field label="Hingga Tarikh" col="col-6 col-md-3">
            <input type="date" wire:model.live="dateTo" class="form-control">
        </x-filter-field>
    </x-filter-bar>

    <div class="card data-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th>Tarikh / Masa</th>
                            <th>Pelaku</th>
                            <th>IP Address</th>
                            <th>Method</th>
                            <th>URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr wire:key="audit-{{ $log->id }}">
                                <td class="text-muted">{{ $loop->iteration + ($logs->currentPage() - 1) * $logs->perPage() }}</td>
                                <td class="text-nowrap">{{ $log->created_at?->format('d/m/Y h:i A') }}</td>
                                <td>{{ $log->user->name ?? '—' }}</td>
                                <td><small>{{ $log->ip }}</small></td>
                                <td>
                                    <span class="badge {{ $log->method === 'GET' ? 'bg-info' : 'bg-warning' }}">{{ $log->method }}</span>
                                </td>
                                <td class="text-truncate" style="max-width:360px"><small>{{ $log->url }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state"><i class="bi bi-clipboard-x"></i> Tiada log dijumpai.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="px-2 pt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <small class="text-muted">Jumlah: {{ number_format($logs->total()) }} log</small>
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
