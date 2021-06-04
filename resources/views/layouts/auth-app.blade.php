<!doctype html>
<html lang="en" >

    <head>

        <meta charset="utf-8" />
        <title>KPLB | KEMENTERIAN PEMBANGUNAN LUAR BANDAR</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
        <meta content="Themesbrand" name="author" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{asset('images/logo.png')}}">

        <!-- Bootstrap Css -->
        <link href="{{asset('assets/css/bootstrap.min.css')}}" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <!-- Icons Css -->
        <link href="{{asset('assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
        <!-- App Css-->
        <link href="{{asset('assets/css/app.min.css')}}" id="app-style" rel="stylesheet" type="text/css" />
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

