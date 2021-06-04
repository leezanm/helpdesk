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
                        <li><a href="{{url('/aduan/create')}}" key="t-aduan-create">Aduan Baru</a></li>
                        <li><a href="{{url('/aduan')}}" key="t-aduan-list">Senarai Aduan</a></li>
                    </ul>
                </li>

                @hasrole('superadmin')
                    <li>
                        <a class="nav-link" href="{{url('/laporan')}}" id="topnav-laporan" role="button">
                            <i class="bx bxs-report me-2"></i><span key="t-laporan">Laporan</span>
                        </a>
                    </li>
                @endhasrole

                {{-- <li>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#search" id="topnav-search" role="button">
                        <i class="bx bx-search-alt-2 me-2"></i><span key="t-search">Carian Rekod</span>
                    </a>
                </li> --}}

                @hasrole('superadmin')
                    <li class="menu-title" key="t-pentadbir">Pentadbir</li>

                    <li>
                        <a class="nav-link" href="{{url('/selenggara')}}" id="topnav-selenggara" role="button">
                            <i class="bx bx-bookmark me-2"></i><span key="t-selenggara">Selenggara</span>
                        </a>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="has-arrow" key="t-vertical">
                            <i class="bx bx-user-plus me-2"></i><span>Pengurusan pengguna</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="true">
                            <li><a href="{{url('/pegawai')}}" key="t-pegawai">Pegawai</a></li>
                            <li><a href="{{url('/pengguna')}}" key="t-pengguna">Pengguna</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="has-arrow" key="t-vertical">
                            <i class="bx bx-receipt me-2"></i><span>Peranan</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="true">
                            <li><a href="{{url('#')}}" key="t-pegawai">Senarai</a></li>
                            <li><a href="{{url('#')}}" key="t-pengguna">Tambah</a></li>
                        </ul>
                    </li>

                    {{-- <li>
                        <a href="javascript: void(0);" class="has-arrow" key="t-vertical">
                            <i class="bx bx-windows me-2"></i><span>Pengurusan Hebahan</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="true">
                            <li><a href="{{url('#')}}" key="t-pegawai">Slider</a></li>
                            <li><a href="{{url('/pengumuman')}}" key="t-pengguna">Pengumuman</a></li>
                        </ul>
                    </li> --}}

                    <li class="menu-title" key="t-pentadbiran">Tetapan</li>

                    <li>
                        <a class="nav-link" href="{{url('/audit-trail')}}" id="topnav-log-sistem" role="button">
                            <i class="bx bx-code-alt me-2"></i><span key="t-audit">Log Sistem</span>
                        </a>
                    </li>

                    {{-- <li>
                        <a class="nav-link" href="{{url('/backup')}}" id="topnav-backup" role="button">
                            <i class="bx bx-cylinder me-2"></i><span key="t-backup">Backup</span>
                        </a>
                    </li> --}}
                @endhasrole

            </ul>

        </div>
    </div>
</div>
@include('mbi.partials.search')


