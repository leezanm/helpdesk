@extends('layouts.auth-app')

@section('content')

<div class="row justify-content-center m-5">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card overflow-hidden">
            <div class="bg-info bg-soft">
                <div class="row">
                    <div class="col-md mt-3" style="text-align:-webkit-center">
                        <img src="{{asset('images/logo.png')}}" alt="" width="150">
                    </div>
                    <div class="col-md-12">
                        <div class="text-primary p-4">
                            <h5 class="text-primary">Selamat Datang </h5>
                            <p>Sila penuhkan maklumat di bawah untuk mendaftar masuk : </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <br>
                <div class="p-2">
                    <form class="form-horizontal" method="POST" action="{{route('login')}}">
                        @csrf
                        <div class="mb-3">
                            <label for="username" class="form-label @error('username') is-invalid @enderror">Nama Pengguna (AD)</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="">
                            @error('username') <p class="text-danger">{{$message}}</p> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kata Laluan</label>
                            <div class="input-group auth-pass-inputgroup">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="" aria-label="Password" aria-describedby="password-addon">
                                <button class="btn btn-light @error('password') border border-danger @enderror" type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
                            </div>
                            @error('password') <p class="text-danger">{{$message}}</p> @enderror
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember-check">
                            <label class="form-check-label" for="remember-check">
                                Kekal sesi login
                            </label>
                        </div>

                        <div class="mt-3 d-grid">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">Log Masuk</button>
                        </div>

                        {{-- <div class="mt-4 text-center">
                            <a href="{{url('/forgot-password-out')}}" class="text-muted"><i class="mdi mdi-lock me-1"></i> Terlupa Kata Laluan?</a>
                        </div> --}}
                    </form>
                </div>
            </div>
        </div>
        <div class="mt-5 text-center">
            <div class="card" style="opacity: 80%;">
                <div class="card-body">
                    © <script>document.write(new Date().getFullYear())</script> KPLB. KEMENTERIAN PEMBANGUNAN LUAR BANDAR
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
