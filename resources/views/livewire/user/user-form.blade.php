<div>
    @if ($showModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form wire:submit="save" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ $userId ? 'Kemaskini Pengguna' : 'Tambah Pengguna' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if ($userId)
                                <div class="alert alert-light border small d-flex align-items-center gap-2 mb-4">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    <span>Hanya <strong>No. Telefon</strong>, <strong>Alamat</strong> dan <strong>Akses (Peranan)</strong> boleh dikemaskini. Maklumat lain diselaraskan daripada Active Directory (AD).</span>
                                </div>
                            @endif

                            @unless ($userId)
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="col-form-label">Gambar Profil</label>
                                        <input type="file" wire:model="profile_path" class="form-control @error('profile_path') is-invalid @enderror">
                                        @error('profile_path') <p class="text-danger">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endunless

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="col-form-label">Nama <span class="text-danger">*</span> @if($userId)<span class="badge bg-light text-muted ms-1">AD</span>@endif</label>
                                    <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror" @if($userId) readonly disabled @endif>
                                    @error('name') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="col-form-label">Nama Pengguna @if($userId)<span class="badge bg-light text-muted ms-1">AD</span>@else<span class="text-danger">*</span>@endif</label>
                                    <input type="text" wire:model="username" class="form-control @error('username') is-invalid @enderror" @if($userId) readonly disabled @endif>
                                    @error('username') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="col-form-label">Emel @if($userId)<span class="badge bg-light text-muted ms-1">AD</span>@else<span class="text-danger">*</span>@endif</label>
                                    <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" @if($userId) readonly disabled @endif>
                                    @error('email') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="col-form-label">Jawatan @if($userId)<span class="badge bg-light text-muted ms-1">AD</span>@endif</label>
                                    <input type="text" wire:model="designation" class="form-control @error('designation') is-invalid @enderror" @if($userId) readonly disabled @endif>
                                    @error('designation') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="col-form-label">No. Telefon</label>
                                    <input type="text" wire:model="tel_no" class="form-control @error('tel_no') is-invalid @enderror" placeholder="Cth: 0388000000">
                                    @error('tel_no') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="col-form-label">No. KP @if($userId)<span class="badge bg-light text-muted ms-1">AD</span>@endif</label>
                                    <input type="text" wire:model="ic_no" class="form-control @error('ic_no') is-invalid @enderror" @if($userId) readonly disabled @endif>
                                    @error('ic_no') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="col-form-label">Alamat</label>
                                    <textarea wire:model="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Alamat pejabat / surat-menyurat"></textarea>
                                    @error('address') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="col-form-label">Akses / Peranan <span class="text-danger">*</span></label>
                                    <div class="border rounded p-2" style="border-color:var(--c-border)!important">
                                        @foreach ($allRoles as $role)
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox"
                                                       id="role-{{ $role->id }}" value="{{ $role->name }}"
                                                       wire:model="roles">
                                                <label class="form-check-label" for="role-{{ $role->id }}">{{ $role->desc }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('roles') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
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
