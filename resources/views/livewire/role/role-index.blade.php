<div>
    <x-filter-bar title="Carian Peranan" icon="bi-search"
                  :show-reset="$search !== ''" reset-action="$set('search','')">
        @can('manage.role')
            <x-slot:action>
                <a class="btn btn-sm btn-primary" wire:click="create" role="button">
                    <i class="bi bi-plus-lg me-1"></i> Peranan Baru
                </a>
            </x-slot:action>
        @endcan

        <x-filter-field label="Carian" col="col-12 col-md-6">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari peranan...">
            </div>
        </x-filter-field>
    </x-filter-bar>

    @if ($message)
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="card data-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th>Peranan</th>
                            <th>Keterangan</th>
                            <th>Pengguna</th>
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
                                <td>{{ $role->users_count }}</td>
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
                                    @can('manage.role')
                                        @if ($role->name !== 'superadmin' || auth()->user()->hasRole('superadmin'))
                                            <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini" wire:click.prevent="edit({{ $role->id }})"><i class="bi bi-pencil"></i></a>
                                        @endif
                                        @unless (in_array($role->name, $systemRoles, true))
                                            <a href="#" class="btn btn-sm btn-outline-danger btn-icon" title="Padam" wire:click.prevent="delete({{ $role->id }})" wire:confirm="Anda pasti untuk memadam peranan ini?"><i class="bi bi-trash"></i></a>
                                        @endunless
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state"><i class="bi bi-shield-lock"></i> Tiada peranan dijumpai.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('manage.role')
        @livewire('role.role-form')
    @endcan
</div>
