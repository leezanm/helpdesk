@extends('layouts.auth-app')

@section('content')
<div class="auth-split">

    {{-- Brand panel --}}
    <div class="auth-brand">
        <div>
            <img src="{{ asset('images/logo.png') }}" alt="KKDW" class="auth-brand__logo">
        </div>
        <div>
            <h1 class="auth-brand__title mb-3">Sistem Meja Bantuan KKDW</h1>
            <p class="auth-brand__sub mb-0">
                Urus dan jejaki aduan anda dengan mudah — satu platform untuk pengguna,
                pegawai teknikal dan pentadbir.
            </p>
        </div>
        <div class="auth-brand__foot">
            © <script>document.write(new Date().getFullYear())</script> Kementerian Kemajuan Desa dan Wilayah
        </div>
    </div>

    {{-- Form panel --}}
    <div class="auth-form-wrap">
        <div class="auth-form-card">
            <div class="text-center mb-4 d-lg-none">
                <img src="{{ asset('images/logo.png') }}" alt="KKDW" style="width:64px">
            </div>

            <h4 class="mb-1">Selamat Datang</h4>
            <p class="text-muted mb-4">Sila log masuk untuk meneruskan.</p>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">ID Pengguna</label>
                    <input type="text" class="form-control auth-input-lg @error('username') is-invalid @enderror"
                           id="username" name="username" placeholder="ID daftar masuk komputer" autofocus>
                    @error('username') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Kata Laluan</label>
                    <div class="input-group">
                        <input type="password" class="form-control auth-input-lg @error('password') is-invalid @enderror"
                               name="password" id="password" placeholder="Kata laluan" aria-describedby="password-addon">
                        <button class="btn btn-outline-secondary" type="button" id="password-addon"
                                onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="remember-check" name="remember">
                    <label class="form-check-label" for="remember-check">Kekal sesi log masuk</label>
                </div>

                <div class="d-grid">
                    <button class="btn btn-primary auth-input-lg" type="submit">Log Masuk</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
