@extends('layouts.app')
@section('breadcrumb')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Butiran Pengguna</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i> Laman Utama</a></li>
                        <li class="breadcrumb-item active">Pengguna</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->
@endsection

@section('content')

    <div class="row g-3">
        {{-- ===== Left: profile identity card ===== --}}
        <div class="col-12 col-lg-4">
            <div class="card profile-card h-100">
                <div class="profile-card__cover"></div>
                <div class="card-body text-center pt-0">
                    <img src="{{ $user->profile_picture }}" class="profile-card__avatar" alt="{{ $user->name }}">
                    <h5 class="mb-1 mt-3 fw-bold">{{ $user->name }}</h5>
                    <p class="text-muted mb-2">{{ $user->designation ?: 'Pengguna' }}</p>

                    <div class="d-flex flex-wrap gap-1 justify-content-center mb-3">
                        @forelse ($user->getRoleNames() as $role)
                            <span class="badge rounded-pill" style="background:var(--c-primary-050);color:var(--c-primary-700)">{{ $role }}</span>
                        @empty
                            <span class="badge rounded-pill bg-light text-muted">Tiada peranan</span>
                        @endforelse
                    </div>

                    @if (isset($user->is_active))
                        <span class="badge rounded-pill {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                            <i class="bi bi-{{ $user->is_active ? 'check-circle' : 'slash-circle' }} me-1"></i>
                            {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== Right: details ===== --}}
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-person-vcard me-1"></i> Maklumat Pengguna</h6></div>
                <div class="card-body">
                    <div class="row g-3 profile-info">
                        <div class="col-sm-6">
                            <div class="profile-info__label">ID Pengguna</div>
                            <div class="profile-info__value">{{ $user->username ?: '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="profile-info__label">Emel</div>
                            <div class="profile-info__value">{{ $user->email ?: '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="profile-info__label">No. Telefon</div>
                            <div class="profile-info__value">{{ $user->tel_no ?: '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="profile-info__label">Jawatan</div>
                            <div class="profile-info__value">{{ $user->designation ?: '-' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="profile-info__label">Bahagian</div>
                            <div class="profile-info__value">{{ $user->ldap_bahagian ?: '-' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="profile-info__label">Alamat</div>
                            <div class="profile-info__value">{{ $user->address ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        @livewire('user.user-asset-manager', ['user' => $user])
    </div>

@endsection
