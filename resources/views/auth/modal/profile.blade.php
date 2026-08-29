@php
    $user = Auth::user()->load('roles');
@endphp

<div id="edit-profile" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editProfileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('account.profile.update', $user) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileLabel"><i class="bi bi-person-gear me-1"></i> Kemaskini Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-light border small d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-info-circle text-primary"></i>
                        <span>Hanya <strong>No. Telefon</strong> dan <strong>Alamat</strong> boleh dikemaskini. Maklumat lain diselaraskan secara automatik daripada Active Directory (AD).</span>
                    </div>

                    {{-- Avatar --}}
                    <div class="text-center mb-4">
                        <img class="rounded-circle mb-2" alt="{{ $user->name }}"
                             src="{{ $user->profile_picture }}" height="96" width="96"
                             style="object-fit:cover;border:3px solid var(--c-border)">
                        <div class="mx-auto" style="max-width:320px">
                            <input type="file" name="image" accept="image/*" class="form-control form-control-sm">
                            <small class="text-muted">Gambar profil (pilihan) — maks 256KB.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- Read-only (from AD) --}}
                        <div class="col-md-6">
                            <label class="form-label">Nama <span class="badge bg-light text-muted ms-1">AD</span></label>
                            <input type="text" class="form-control" value="{{ $user->name }}" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mel <span class="badge bg-light text-muted ms-1">AD</span></label>
                            <input type="text" class="form-control" value="{{ $user->email }}" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jawatan <span class="badge bg-light text-muted ms-1">AD</span></label>
                            <input type="text" class="form-control" value="{{ $user->designation ?: '-' }}" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bahagian <span class="badge bg-light text-muted ms-1">AD</span></label>
                            <input type="text" class="form-control" value="{{ $user->ldap_bahagian ?: '-' }}" readonly disabled>
                        </div>

                        {{-- Editable --}}
                        <div class="col-md-6">
                            <label class="form-label">No. Telefon</label>
                            <input type="text" name="tel_no" class="form-control @error('tel_no') is-invalid @enderror"
                                   value="{{ old('tel_no', $user->tel_no) }}" placeholder="Cth: 03-8000 0000">
                            @error('tel_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Peranan</label>
                            <div class="d-flex flex-wrap gap-1 align-items-center" style="min-height:38px">
                                @forelse ($user->roles as $role)
                                    <span class="badge rounded-pill" style="background:var(--c-primary-050);color:var(--c-primary-700)">{{ $role->desc }}</span>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror"
                                      placeholder="Alamat pejabat / surat-menyurat">{{ old('address', $user->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Kemaskini</button>
                </div>
            </form>
        </div>
    </div>
</div>
