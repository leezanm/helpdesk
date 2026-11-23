@extends('layouts.auth-app')

@section('content')
<div class="auth-split">

    {{-- Brand / hero panel --}}
    <div class="auth-brand">
        <div class="auth-brand__top">
            <img src="{{ asset('images/logo.png') }}" alt="KKDW" class="auth-brand__logo">
            <span class="auth-brand__badge"><i class="bi bi-shield-check me-1"></i>Sistem Rasmi KKDW</span>
        </div>

        <div class="auth-brand__mid">
            <p class="auth-brand__eyebrow"><i class="bi bi-grid-1x2-fill me-2"></i>Sistem Meja Bantuan</p>
            <h1 class="auth-brand__title mb-3">
                Satu platform digital<br>untuk setiap aduan warga kementerian.
            </h1>
            <p class="auth-brand__sub mb-4">
                Kami mendigitalkan dan menyusun aduan dari hujung ke hujung — dari
                pelaporan, penugasan, sehingga penyelesaian — supaya kerja seluruh
                warga KKDW menjadi lebih pantas, telus dan teratur.
            </p>

            <ul class="auth-features">
                <li><span class="auth-features__ic"><i class="bi bi-send-check"></i></span>
                    <div><strong>Hantar aduan dalam beberapa klik</strong><small>Borang ringkas, lampiran &amp; bagi pihak</small></div></li>
                <li><span class="auth-features__ic"><i class="bi bi-diagram-3"></i></span>
                    <div><strong>Jejak status masa nyata</strong><small>Baru → Dalam Tindakan → Selesai</small></div></li>
                {{-- <li><span class="auth-features__ic"><i class="bi bi-lightbulb"></i></span>
                    <div><strong>Knowledge Base</strong><small>Punca &amp; penyelesaian dikongsi pegawai</small></div></li> --}}
                <li><span class="auth-features__ic"><i class="bi bi-bar-chart-line"></i></span>
                    <div><strong>Dashboard pengurusan</strong><small>Analitik &amp; laporan untuk keputusan</small></div></li>
            </ul>
        </div>


    </div>

    {{-- Form panel --}}
    <div class="auth-form-wrap">
        <div class="auth-form-card">
            <div class="text-center mb-4 d-lg-none">
                <img src="{{ asset('images/logo.png') }}" alt="KKDW" style="width:64px">
            </div>

            <h4 class="mb-1 text-uppercase">Selamat Datang</h4>
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

                @if (!empty($captchaQuestion))
                    <div class="mb-3">
                        <label class="form-label">Pengesahan Keselamatan</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ $captchaQuestion }} =</span>
                            <input type="text" inputmode="numeric" autocomplete="off"
                                   class="form-control auth-input-lg @error('captcha') is-invalid @enderror"
                                   name="captcha" placeholder="Jawapan">
                        </div>
                        <small class="text-muted">Sila selesaikan operasi matematik di atas untuk mengesahkan anda bukan robot.</small>
                        @error('captcha') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif



                <div class="d-grid">
                    <button class="btn btn-primary auth-input-lg" type="submit">Log Masuk</button>
                </div>
            </form>

            {{-- QR to the live system, for logging in from a mobile device --}}
            @php($loginQr = qrCodeDataUri('https://helpdesk.rurallink.gov.my', 150))
            @if ($loginQr)
                <div class="auth-qr text-center mt-4 pt-4 border-top" style="border-color:var(--c-border)!important">
                    <img src="{{ $loginQr }}" alt="QR ke helpdesk.rurallink.gov.my"
                         width="130" height="130" class="mb-2">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-phone me-1"></i>Imbas untuk log masuk melalui telefon
                    </p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
