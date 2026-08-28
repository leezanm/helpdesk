<div>
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                           placeholder="Cari nama / username / emel...">
                </div>
                <div class="col text-end">
                    <a class="btn btn-primary waves-effect waves-light" wire:click="create" role="button">
                        <i class="bx bxs-plus-square font-size-16 align-middle me-2"></i> Tambah Baru
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="text-center align-middle">
                        <th>#</th>
                        <th>Nama Pegawai</th>
                        <th>Peranan</th>
                        <th>Status</th>
                        <th>Tindakan</th>
                    </thead>
                    <tbody class="text-center align-middle">
                        @forelse ($users as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td>{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                                <td class="text-start p-0">
                                    <div class="media align-items-center">
                                        <div class="me-3">
                                            <img src="{{ $user->profile_picture }}" alt="" class="avatar-sm h-auto d-block rounded">
                                        </div>
                                        <div class="media-body">
                                            <h5 class="font-size-13">{{ $user->name }}</h5>
                                            <p class="mb-0">{{ $user->username }} | {{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-start">
                                    @forelse ($user->roles->sortBy('desc') as $userRole)
                                        <span class="badge bg-info font-size-12">{{ $userRole->desc }}</span>
                                    @empty
                                        -
                                    @endforelse
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="badge bg-success">AKTIF</span>
                                    @else
                                        <span class="badge bg-danger">TIDAK AKTIF</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('user.showFullUser', $user->id) }}" class="btn btn-sm btn-rounded rounded-circle btn-outline-info"> <i class="mdi mdi-eye"></i></a>
                                    <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-primary"
                                       wire:click.prevent="edit({{ $user->id }})"> <i class="bx bxs-pen"></i></a>
                                    @if ($user->is_active)
                                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-danger"
                                           wire:click.prevent="deactivate({{ $user->id }})"
                                           wire:confirm="Anda pasti?"> <i class="bx bx-lock"></i></a>
                                    @else
                                        <a href="#" class="btn btn-sm btn-rounded rounded-circle btn-outline-success"
                                           wire:click.prevent="restore({{ $user->id }})"
                                           wire:confirm="Aktifkan semula pengguna ini?"> <i class="bx bx-lock-open"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Tiada pengguna dijumpai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    @livewire('user.user-form')
</div>
