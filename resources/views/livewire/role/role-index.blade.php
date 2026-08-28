<div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                           placeholder="Cari peranan...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered dt-responsive">
                <thead class="text-center align-middle">
                    <tr>
                        <th>No</th>
                        <th>Peranan</th>
                        <th>Keterangan</th>
                        <th>Permissions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="text-center align-middle">
                    @forelse ($roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->desc }}</td>
                            <td class="text-start">
                                <ul>
                                    @foreach ($role->permissions as $permission)
                                        <li>{{ $permission->desc }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <th><a href="{{ route('role.show', $role->name) }}">lihat</a></th>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Tiada peranan dijumpai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
