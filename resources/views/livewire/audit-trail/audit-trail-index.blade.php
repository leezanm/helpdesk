<div>
    <div class="list-toolbar">
        <div class="list-search">
            <i class="bi bi-search"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                   placeholder="Cari URL, IP, atau nama pengguna...">
        </div>
        <select wire:model.live="method" class="form-select" style="max-width:180px">
            <option value="">Semua Method</option>
            <option value="GET">GET</option>
            <option value="POST">POST</option>
            <option value="PUT">PUT</option>
            <option value="PATCH">PATCH</option>
            <option value="DELETE">DELETE</option>
        </select>
    </div>

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
