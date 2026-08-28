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
                                    <label class="col-form-label">Nama <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror">
                                    @error('name') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="col-form-label">Nama Pengguna (AD) <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="username" class="form-control @error('username') is-invalid @enderror" @if($userId) disabled @endif>
                                    @error('username') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="col-form-label">Emel <span class="text-danger">*</span></label>
                                    <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" @if($userId) readonly @endif>
                                    @error('email') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="col-form-label">Jawatan</label>
                                    <input type="text" wire:model="designation" class="form-control @error('designation') is-invalid @enderror">
                                    @error('designation') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="col-form-label">No. Telefon</label>
                                    <input type="text" wire:model="tel_no" class="form-control @error('tel_no') is-invalid @enderror">
                                    @error('tel_no') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="col-form-label">No. KP</label>
                                    <input type="text" wire:model="ic_no" class="form-control @error('ic_no') is-invalid @enderror">
                                    @error('ic_no') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="col-form-label">Alamat</label>
                                    <textarea wire:model="address" class="form-control @error('address') is-invalid @enderror" rows="2"></textarea>
                                    @error('address') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="col-form-label">Peranan <span class="text-danger">*</span></label>
                                    <div>
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
