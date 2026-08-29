<!doctype html>
<html lang="en">

    <head>

        <meta charset="utf-8" />
        <title>KKDW | KEMENTERIAN KEMAJUAN DESA DAN WILAYAH</title>
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

        <!-- Vite (app entry) + Livewire styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
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

                    @livewire('shared.notification-bell')

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
                                <script>document.write(new Date().getFullYear())</script> © KKDW. KEMENTERIAN KEMAJUAN DESA DAN WILAYAH
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
        @livewireScripts
    </body>
</html>
