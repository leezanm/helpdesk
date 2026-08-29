<div>
    <div class="list-toolbar">
        <div class="list-search">
            <i class="bi bi-search"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari peranan...">
        </div>
    </div>

    <div class="card data-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th>Peranan</th>
                            <th>Keterangan</th>
                            <th>Kebenaran (Permissions)</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr wire:key="role-{{ $role->id }}">
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td><span class="badge bg-primary">{{ $role->name }}</span></td>
                                <td class="fw-medium">{{ $role->desc }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($role->permissions as $permission)
                                            <span class="badge bg-secondary">{{ $permission->desc }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('role.show', $role->name) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Lihat"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-shield-lock"></i> Tiada peranan dijumpai.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
