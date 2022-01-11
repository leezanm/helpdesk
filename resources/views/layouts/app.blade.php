<!doctype html>
<html lang="en">

    <head>

        <meta charset="utf-8" />
        <title>KPLB | KEMENTERIAN PEMBANGUNAN LUAR BANDAR</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
        <meta content="Themesbrand" name="author" />
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{asset('images/logo.png')}}">

        @include('layouts.css')
        <!-- DataTables -->
        <link href="{{asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" type="text/css" />
        <link href="{{asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css')}}" rel="stylesheet" type="text/css" />

        <!-- Responsive datatable examples -->
        <link href="{{asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')}}" rel="stylesheet" type="text/css" />

        <!-- Bootstrap Css -->
        <link href="{{asset('assets/css/bootstrap.min.css')}}" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <!-- Icons Css -->
        <link href="{{asset('assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
        <!-- App Css-->
        <link href="{{asset('assets/css/app.min.css')}}" id="app-style" rel="stylesheet" type="text/css" />
    </head>

    <body data-sidebar="dark">

        <!-- Begin page -->
        <div id="layout-wrapper">

            <header id="page-topbar">
                <div class="navbar-header">
                    <div class="d-flex">
                        <!-- LOGO -->
                        <div class="navbar-brand-box">
                            <a href="{{url('/')}}" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="{{asset('assets/images/logo.png')}}" alt="" height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{asset('assets/images/logo-dark.png')}}" alt="" height="35">
                                </span>
                            </a>

                            <a href="{{url('/')}}" class="logo logo-light">
                                <span class="logo-sm">
                                    <img src="{{asset('images/logo.png')}}" alt="" height="50">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{asset('images/logo-long.png')}}" alt="" height="40">
                                </span>
                            </a>
                        </div>

                        <button type="button" class="btn btn-sm px-3 font-size-16 d-lg-none header-item waves-effect waves-light" id="vertical-menu-btn"  data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                            <i class="fa fa-fw fa-bars"></i>
                        </button>
                    </button>

                        <h5 class="app-search d-none d-lg-block m-3">
                            Selamat Datang , {{Auth::user()->name}}
                        </h5>

                    </div>

                    <div class="d-flex">

                    @php
                        $notifications = Auth::user()->unreadNotifications;
                        foreach ($notifications as $notification) {
                            $created_at_hr = $notification->created_at->diffForHumans();
                            $created_at_hr = str_replace('detik', 'saat', $created_at_hr);
                            $created_at_hr = str_replace('menit', 'minit', $created_at_hr);
                            $created_at_hr = str_replace(' yang lalu', '', $created_at_hr);
                            $notification->setAttribute('created_at_hr', $created_at_hr);
                        }
                    @endphp
                    <div class="dropdown d-inline-block">
                        <button class="btn header-item noti-icon waves-effect" id="bpage-header-notifications-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            <i class="bx bx-bell bx-tada"></i>
                            <span id="notiCount" class="badge bg-danger rounded-pill">{{ $notifications->count() }}</span>
                        </button>
                        {{-- <a href="" class="btn header-item noti-icon waves-effect"  onclick="checkNotification()"><i class="bx bx-bell bx-tada"></i></a> --}}
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">
                            <div class="px-3 py-2 bg-primary bg-soft">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0" key="t-notifications"> Notifikasi </h6>
                                    </div>
                                </div>
                            </div>
                            <div id="notiList" data-simplebar style="max-height: 410px;">
                                @foreach ($notifications as $notification)
                                    <a href="#" class="text-reset redirect notification-item" data-notificationId="{{ $notification->id }}">
                                        <div class="media">
                                            <div class="avatar-xs me-3">
                                                <span class="avatar-title {{$notification->data['color']}} rounded-circle font-size-16 align-middle">
                                                    <i class="{{ $notification->data['icon'] }} align-middle"></i>
                                                </span>
                                            </div>
                                            <div class="media-body">
                                                <h6 class="mt-0 mb-1" key="t-your-order">
                                                    {{ $notification->data['title'] }}</h6>
                                                <div class="font-size-12 text-muted">
                                                    <p class="mb-1" key="t-grammer">
                                                        {{ $notification->data['message'] }}</p>
                                                    <p class="mb-0"><i class="mdi mdi-clock-outline"></i> <span
                                                            key="t-min-ago">{{ $notification->created_at_hr }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>

                        </div>
                    </div>

                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img class="rounded-circle header-profile-user" src="{{Auth::user()->profile_picture}}"
                                    alt="Header Avatar">
                                <span class="d-none d-xl-inline-block ms-1" key="t-henry">{{Auth::user()->name}}</span>
                                <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit-profile">
                                    <i class="bx bx-user font-size-16 align-middle me-1"></i> <span
                                        key="t-profile">Profil</span></a>
                                {{-- <a class="dropdown-item d-block" href="#" data-bs-toggle="modal"
                                    data-bs-target="#password-reset"><i
                                        class="bx bxs-key font-size-16 align-middle me-1"></i> <span
                                        key="t-settings">Tukar Kata Laluan</span></a> --}}

                                {{-- <a class="dropdown-item" href="{{ route('account.profile.edit', Auth::user()->id) }}"> <i class="bx bx-user font-size-16 align-middle me-1"></i> <span key="t-profile">Profil</span></a>
                                <a class="dropdown-item d-block" href="{{ route('account.password.edit', Auth::user()->id) }}"><i class="bx bxs-key font-size-16 align-middle me-1"></i> <span key="t-settings">Tukar Kata Laluan</span></a> --}}
                                {{-- <a class="dropdown-item" href="#"><i class="bx bx-lock-open font-size-16 align-middle me-1"></i> <span key="t-lock-screen">Kunci Skrin</span></a> --}}
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> <span key="t-logout">Log Keluar</span></a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </header>

            @include('layouts.navbar')

            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->
            <div class="main-content">

                <div class="page-content">
                    <div class="container-fluid">
                        @yield('breadcrumb')
                        @yield('content')
                    </div>
                </div>
                <!-- End Page-content -->

                <footer class="footer">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-6">
                                <script>document.write(new Date().getFullYear())</script> © KPLB. KEMENTERIAN PEMBANGUNAN LUAR BANDAR
                            </div>
                            {{-- <div class="col-sm-6">
                                <div class="text-sm-end d-none d-sm-block">
                                    Design & Develop by Themesbrand
                                </div>
                            </div> --}}
                        </div>
                    </div>
                </footer>
            </div>
            <!-- end main content-->

        </div>
        <!-- END layout-wrapper -->
        @include('auth.modal.reset_password')
        @include('auth.modal.profile')
        @include('partials.modal.profile_modal')

        @include('layouts.script')
        @yield('script')
        <script src="{{asset('js/user.js')}}"></script>
        <script>
            $('.updatePassword').on('click', function() {

                var form = $(this).closest('form');
                var formData = new FormData(form[0]);
                formData.append('_token', '{{ csrf_token() }}');
                clearError(form);
                $.ajax({
                    url: form.attr('action'),
                    method: form.attr('method'),
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // resetForm(formId);
                            window.location.reload();
                        }
                    },
                    error: function(errorResponse) {
                        if (errorResponse.responseJSON.errors) {
                            errorValidate(errorResponse, form);
                        }
                    }
                });
            });
        </script>
    </body>
</html>
