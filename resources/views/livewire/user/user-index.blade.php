<div>
    <x-filter-bar title="Carian Pengguna" icon="bi-search"
                  :show-reset="$search !== '' || $bahagian !== ''" reset-action="resetFilters">
        <x-slot:action>
            <a class="btn btn-sm btn-primary" wire:click="create" role="button">
                <i class="bi bi-plus-lg me-1"></i> Pengguna Baru
            </a>
        </x-slot:action>

        <x-filter-field label="Carian" col="col-12 col-md-6">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari nama, username atau emel...">
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
    </x-filter-bar>

    <div class="card data-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th>Nama Pegawai</th>
                            <th>Peranan</th>
                            <th>Status</th>
                            <th class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td class="text-muted">{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $user->profile_picture }}" alt="" style="width:42px;height:42px;object-fit:cover;border-radius:50%;">
                                        <div>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <small class="text-muted">{{ $user->username }} · {{ $user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @forelse ($user->roles->sortBy('desc') as $userRole)
                                        <span class="badge bg-info">{{ $userRole->desc }}</span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="badge bg-success">AKTIF</span>
                                    @else
                                        <span class="badge bg-danger">TIDAK AKTIF</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('user.showFullUser', $user) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Lihat"><i class="bi bi-eye"></i></a>
                                    <a href="#" class="btn btn-sm btn-outline-secondary btn-icon" title="Kemaskini" wire:click.prevent="edit({{ $user->id }})"><i class="bi bi-pencil"></i></a>
                                    @if ($user->is_active)
                                        <a href="#" class="btn btn-sm btn-outline-danger btn-icon" title="Nyahaktif" wire:click.prevent="deactivate({{ $user->id }})" wire:confirm="Anda pasti?"><i class="bi bi-lock"></i></a>
                                    @else
                                        <a href="#" class="btn btn-sm btn-outline-success btn-icon" title="Aktifkan" wire:click.prevent="restore({{ $user->id }})" wire:confirm="Aktifkan semula pengguna ini?"><i class="bi bi-unlock"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-people"></i> Tiada pengguna dijumpai.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="px-2 pt-3">{{ $users->links() }}</div>
            @endif
        </div>
    </div>

    @livewire('user.user-form')
</div>
