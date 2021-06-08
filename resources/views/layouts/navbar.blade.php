<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">Laman Utama</li>
                <li>
                    <a class="nav-link" href="{{url('/dashboard')}}" id="topnav-dashboard" role="button">
                        <i class="bx bx-home-circle me-2"></i><span key="t-dashboards">Utama</span>
                    </a>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow" key="t-vertical">
                        <i class="bx bx-file-blank me-2"></i><span key="t-fbi">Aduan</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="true">

                        @hasrole('pengguna')
                            <div class="dropdown-divider"></div>
                            <li class="bg-light"><a href="javascript: void(0);" class="text-info"><strong>PENGGUNA</strong></a></li>
                            <div class="dropdown-divider"></div>

                
                            <li><a href="{{url('/aduan/create')}}" key="t-aduan-create">Aduan Baru</a></li>
                            <li><a href="{{url('/aduan')}}" key="t-aduan-list">Aduan Saya</a></li>
                        @endhasrole

                        @hasanyrole('jtk|ptm|pptm')
                            <div class="dropdown-divider"></div>
                            <li class="bg-light"><a href="javascript: void(0);" class="text-info"><strong>PEG TEKNIKAL</strong></a></li>
                            <div class="dropdown-divider"></div>

                            <li><a href="{{url('/aduan')}}" key="t-aduan-create">Senarai Aduan</a></li>
                            <li><a href="{{url('/aduan/untuk-tindakan')}}" key="t-aduan-list">Untuk Tindakan</a></li>
                        @endhasanyrole

                        @hasrole('helpdesk')
                            <div class="dropdown-divider"></div>
                            <li class="bg-light"><a href="javascript: void(0);" class="text-info"><strong>PENTADBIR HELPDESK</strong></a></li>
                            <div class="dropdown-divider"></div>

                            <li><a href="{{url('/aduan')}}" key="t-aduan-create">Senarai Aduan</a></li>
                            <li><a href="{{url('/aduan/dihantar')}}" key="t-aduan-list">Aduan Dihantar</a></li>
                        @endhasrole
                    </ul>
                </li>

                @can('view.report')
                    <li>
                        <a class="nav-link" href="{{url('/laporan')}}" id="topnav-laporan" role="button">
                            <i class="bx bxs-report me-2"></i><span key="t-laporan">Laporan</span>
                        </a>
                    </li>
                @endcan

                {{-- <li>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#search" id="topnav-search" role="button">
                        <i class="bx bx-search-alt-2 me-2"></i><span key="t-search">Carian Rekod</span>
                    </a>
                </li> --}}

                @canany(['manage.division','manage.category', 'manage.admin', 'manage.role'])
                    <li class="menu-title" key="t-pentadbir">Pentadbiran</li>
                @endcanany
                

                @canany(['manage.division','manage.category'])
                <li>
                    <a href="javascript: void(0);" class="has-arrow" key="t-vertical">
                        <i class="bx bx-cog me-2"></i><span>Selenggaraan</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="true">
                        @can('manage.division')
                            <li><a href="{{route('division.index')}}" key="t-division">Bahagian</a></li>
                        @endcan
                        @can('manage.category')
                            <li><a href="{{route('type.index')}}" key="t-category">Kategori Aduan</a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                @canany(['manage.user','manage.admin'])
                    <li>
                        <a href="javascript: void(0);" class="has-arrow" key="t-vertical">
                            <i class="bx bxs-group me-2"></i><span>Pengurusan pengguna</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="true">
                            @can('manage.admin')
                                <li><a href="{{url('/pentadbir')}}" key="t-pegawai">Pentadbir Helpdesk</a></li>
                            @endcan
                            @can('manage.user')
                                <li><a href="{{url('/pengguna')}}" key="t-pengguna">Pengguna</a></li>
                                <li><a href="{{url('/peg-teknikal')}}" key="t-pengguna">Peg. Teknikal</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @can('manage.role')
                    <li>
                        <a class="nav-link" href="{{url('/peranan')}}" id="topnav-role-management" role="button">
                            <i class="bx bxs-user-badge me-2"></i><span key="t-role-management">Pengurusan Peranan</span>
                        </a>
                    </li>
                @endcan

                {{-- <li>
                    <a href="javascript: void(0);" class="has-arrow" key="t-vertical">
                        <i class="bx bx-receipt me-2"></i><span>Peranan</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="true">
                        <li><a href="{{url('/peranan')}}" key="t-pegawai">Senarai</a></li>
                        <li><a href="{{url('#')}}" key="t-pengguna">Tambah</a></li>
                    </ul>
                </li> --}}

                {{-- <li>
                    <a href="javascript: void(0);" class="has-arrow" key="t-vertical">
                        <i class="bx bx-windows me-2"></i><span>Pengurusan Hebahan</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="true">
                        <li><a href="{{url('#')}}" key="t-pegawai">Slider</a></li>
                        <li><a href="{{url('/pengumuman')}}" key="t-pengguna">Pengumuman</a></li>
                    </ul>
                </li> --}}

                @canany(['manage.audit-trail', 'manage.backup'])
                    <li class="menu-title" key="t-pentadbiran">Tetapan</li>

                    <li>
                        <a class="nav-link" href="{{url('/audit-trail')}}" id="topnav-log-sistem" role="button">
                            <i class="bx bx-code-alt me-2"></i><span key="t-audit">Log Sistem</span>
                        </a>
                    </li>

                    <li>
                        <a class="nav-link" href="{{url('/backup')}}" id="topnav-backup" role="button">
                            <i class="bx bx-data me-2"></i><span key="t-backup">Backup</span>
                        </a>
                    </li>
                @endcanany

            </ul>

        </div>
    </div>
</div>
@include('mbi.partials.search')


