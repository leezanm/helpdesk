@extends('layouts.auth-app')


@section('content')

<div class="account-pages my-5 pt-sm-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card overflow-hidden">
                    <div class="bg-primary bg-soft">
                        <div class="row">
                            <div class="col">
                                <div class="text-primary p-4">
                                    <h5 class="text-primary"> Tetapkan semula Kata Laluan</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div>
                            <a href="index.html">
                                <div class="avatar-md profile-user-wid mb-4">
                                    <span class="avatar-title rounded-circle bg-light">
                                        <img src="{{ asset('images/logo.png') }}" alt="" class="rounded-circle" height="34">
                                    </span>
                                </div>
                            </a>
                        </div>

                        <div class="p-2">
                            <div class="alert alert-success text-center mb-4" role="alert">
                                Masukkan E-mel anda dan arahan akan dihantar kepada anda!
                            </div>
                            <form class="form-horizontal" action="index.html">

                                <div class="mb-3">
                                    <label for="useremail" class="form-label">E-mel</label>
                                    <input type="email" class="form-control" id="useremail" placeholder="Masukkan e-mel">
                                </div>

                                <div class="text-end">
                                    {{-- <button class="btn btn-primary w-md waves-effect waves-light" type="submit">Hantar</button> --}}
                                    <a href="{{url('/')}}" class="btn btn-primary">Hantar</a>
                                </div>

                            </form>
                        </div>

                    </div>
                </div>
                <div class="mt-5 text-center">
                    <p>© <script>document.write(new Date().getFullYear())</script> KPLB. KEMENTERIAN PEMBANGUNAN LUAR BANDAR</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')

@endsection
