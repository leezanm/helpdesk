<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title>KKDW | KEMENTERIAN KEMAJUAN DESA DAN WILAYAH</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Sistem Meja Bantuan KKDW" name="description" />
        <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">

        {{--
            Stock Bootstrap 5 + Bootstrap Icons from CDN. We deliberately do NOT
            load the old Skote theme's customized bootstrap.min.css / app.min.css
            (they hardcode the old #556ee6 / Poppins identity). We keep the old
            boxicons/mdi icon font only, for existing `bx`/`mdi` icon classes.
        --}}
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

        {{-- ECharts loaded in <head> so it is ready before Livewire runs chart scripts --}}
        <script src="{{ asset('assets/libs/echarts/echarts.min.js') }}"></script>

        {{-- Custom theme (loads last so it fully owns the look) --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>

    <body>
        <div class="app-shell" x-data="{ open: false }" :class="{ 'sidebar-open': open }">

            {{-- Backdrop for mobile drawer --}}
            <div class="app-backdrop" x-on:click="open = false"></div>

            {{-- Sidebar --}}
            <aside class="app-sidebar">
                <a href="{{ url('/dashboard') }}" class="app-sidebar__brand">
                    <img src="{{ asset('images/logo.png') }}" alt="KKDW">
                    <span>KKDW Helpdesk</span>
                </a>
                @include('layouts.navbar')
            </aside>

            {{-- Main --}}
            <div class="app-main">
                <header class="app-topbar">
                    <button type="button" class="app-hamburger" x-on:click="open = !open" aria-label="Menu">
                        <i class="bx bx-menu"></i>
                    </button>
                    <span class="app-topbar__greeting d-none d-md-inline">Selamat Datang, {{ Auth::user()->name }}</span>
                    <div class="app-topbar__spacer"></div>

                    @livewire('shared.notification-bell')

                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" src="{{ Auth::user()->profile_picture }}" alt="">
                            <span class="d-none d-xl-inline-block fw-semibold">{{ Auth::user()->name }}</span>
                            <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit-profile">
                                <i class="bx bx-user font-size-16 align-middle me-1"></i> <span>Profil</span></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> <span>Log Keluar</span></a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                        </div>
                    </div>
                </header>

                <main class="app-content">
                    @yield('breadcrumb')
                    @yield('content')
                </main>

                <footer class="app-footer">
                    <script>document.write(new Date().getFullYear())</script> © KKDW. KEMENTERIAN KEMAJUAN DESA DAN WILAYAH
                </footer>
            </div>
        </div>

        @include('auth.modal.reset_password')
        @include('auth.modal.profile')
        @include('partials.modal.profile_modal')

        @include('layouts.script')
        @yield('script')
        <script src="{{ asset('js/user.js') }}"></script>
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
                        if (response.success) { window.location.reload(); }
                    },
                    error: function(errorResponse) {
                        if (errorResponse.responseJSON.errors) { errorValidate(errorResponse, form); }
                    }
                });
            });
        </script>
    </body>
</html>
