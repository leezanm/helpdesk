<div>
    @if ($showModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $roleId ? 'Kemaskini Peranan' : 'Tambah Peranan' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="col-form-label">Nama Peranan <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="desc" class="form-control @error('desc') is-invalid @enderror" placeholder="cth. Penyelia">
                                    @error('desc') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="col-form-label">Nama Kod @unless ($roleId) <span class="text-danger">*</span> @endunless</label>
                                    <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror"
                                           placeholder="cth. penyelia.kanan" @disabled($roleId)>
                                    @error('name') <p class="text-danger">{{ $message }}</p> @enderror
                                    <div class="form-text">
                                        @if ($roleId) Nama kod tidak boleh diubah. @else Huruf kecil, nombor, titik/sengkang. Tidak boleh diubah selepas disimpan. @endif
                                    </div>
                                </div>
                            </div>

                            <label class="col-form-label">Kebenaran (Permissions)</label>
                            @if ($permissionsLocked)
                                <div class="alert alert-info py-2">Admin Sistem sentiasa memegang semua kebenaran.</div>
                            @elseif ($isSystemRole)
                                <div class="alert alert-warning py-2">Peranan sistem: sebahagian fungsi dirujuk mengikut nama kod peranan ini.</div>
                            @endif
                            @error('permissions.*') <p class="text-danger">{{ $message }}</p> @enderror

                            <div class="row">
                                @foreach ($allPermissions->groupBy(fn ($p) => explode('.', $p->name)[0]) as $group => $items)
                                    <div class="col-md-6 mb-3">
                                        <div class="fw-semibold text-uppercase small text-muted mb-1">{{ $group }}</div>
                                        @foreach ($items as $permission)
                                            @php($held = in_array($permission->name, $ownPermissions, true))
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm-{{ $permission->id }}"
                                                       value="{{ $permission->name }}" wire:model="permissions"
                                                       @disabled($permissionsLocked || ! $held)>
                                                <label class="form-check-label" for="perm-{{ $permission->id }}">
                                                    {{ $permission->desc }} <code class="small">{{ $permission->name }}</code>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="modal-footer text-end">
                            <button class="btn btn-secondary" type="button" wire:click="closeModal">Batal</button>
                            <button class="btn btn-success" type="submit" wire:loading.attr="disabled">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
