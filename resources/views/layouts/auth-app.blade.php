<!doctype html>
<html lang="en" >

    <head>

        <meta charset="utf-8" />
        <title>KKDW | KEMENTERIAN KEMAJUAN DESA DAN WILAYAH</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
        <meta content="Themesbrand" name="author" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{asset('images/logo.png')}}">

        {{-- Stock Bootstrap 5 + icons (old Skote theme CSS dropped) --}}
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

        {{-- Custom theme (loads last so it owns the look) --}}
        @vite(['resources/css/app.css'])
        <style>

        </style>
    </head>

    <body   style="
                background: url(/images/backgrrund.jpg) !important;
                background: aliceblue;
                background-size: cover !important;
    ">
        <div class="account-pages my-5 pt-sm-5">
            <div class="container">
                @yield('content')
            </div>
        </div>
        <!-- end account-pages -->
        @include('layouts.script')
        @yield('script')
    </body>
</html>

