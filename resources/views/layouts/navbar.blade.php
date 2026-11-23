@php $path = request()->path(); @endphp
<nav class="app-sidebar__nav">

    <div class="app-sidebar__title">Laman Utama</div>
    @can('access-main-dashboard')
        <a class="app-nav-link {{ str_starts_with($path, 'dashboard/utama') ? 'active' : '' }}" href="{{ route('dashboard.main') }}">
            <i class="bx bx-bar-chart-square"></i><span>Dashboard Pengurusan</span>
        </a>
    @endcan
    <a class="app-nav-link {{ $path === 'dashboard' ? 'active' : '' }}" href="{{ url('/dashboard') }}">
        <i class="bx bx-home-circle"></i><span>Dashboard Saya</span>
    </a>
    @can('aduan.updateTask')
        <a class="app-nav-link {{ str_contains($path, 'lesson-learn') ? 'active' : '' }}" href="{{ route('aduan.lesson-learn') }}">
            <i class="bx bx-book-bookmark"></i><span>Knowledge Base</span>
        </a>
    @endcan

    {{-- Aduan. Knowledge Base berkongsi prefix URL 'aduan/' tetapi kini satu
         item peringkat atas, jadi ia dikecualikan daripada syarat buka ini. --}}
    <div x-data="{ o: {{ (str_starts_with($path, 'aduan') && ! str_contains($path, 'lesson-learn')) ? 'true' : 'false' }} }">
        <a class="app-nav-link" x-on:click="o = !o" role="button">
            <i class="bx bx-file-blank"></i><span>Aduan</span>
            <i class="bx bx-chevron-down ms-auto" x-bind:style="o && 'transform:rotate(180deg)'"></i>
        </a>
        <ul class="app-nav-sub" x-show="o" x-collapse>
            <li><a class="app-nav-link {{ $path === 'aduan/create' ? 'active' : '' }}" href="{{ url('/aduan/create') }}"><span>Cipta Aduan</span></a></li>
            <li><a class="app-nav-link {{ str_starts_with($path, 'aduan/user') ? 'active' : '' }}" href="{{ url('/aduan/user') }}/{{ Auth::user()->username }}"><span>Aduan Saya</span></a></li>

            @can('aduan.updateTask')
                <li class="app-sidebar__title">Peg Teknikal</li>
                <li><a class="app-nav-link {{ str_starts_with($path, 'aduan/untuk-tindakan') ? 'active' : '' }}" href="{{ route('aduan.index-untuk-tindakan') }}"><span>Untuk Tindakan</span></a></li>
                <li><a class="app-nav-link {{ str_starts_with($path, 'aduan/selesai-saya') ? 'active' : '' }}" href="{{ route('aduan.index-selesai') }}"><span>Senarai Aduan</span></a></li>
            @endcan

            @can('aduan.adminTask')
                <li class="app-sidebar__title">Pentadbir Helpdesk</li>
                <li><a class="app-nav-link {{ $path === 'aduan/dihantar' ? 'active' : '' }}" href="{{ url('/aduan/dihantar') }}"><span>Aduan Baru</span></a></li>
            @endcan

            @canany(['aduan.adminTask','aduan.updateTask'])
                {{-- Padanan tepat: prefix 'aduan' akan bertindih dengan setiap submenu aduan/* lain. --}}
                <li><a class="app-nav-link {{ $path === 'aduan' ? 'active' : '' }}" href="{{ url('/aduan') }}"><span>Senarai Aduan</span></a></li>
            @endcanany
        </ul>
    </div>

    {{-- Pergerakan pegawai. Buat masa ini hanya penyelenggaraan roster yang
         ada skrin; Jadual Bulanan dan Senarai Pergerakan ditambah di sini
         bersama skrinnya dalam task berikutnya. --}}
    @canany(['pergerakan.view','pergerakan.create','pergerakan.manageRoster'])
        <div x-data="{ o: {{ str_starts_with($path, 'pergerakan') ? 'true' : 'false' }} }">
            <a class="app-nav-link" x-on:click="o = !o" role="button">
                <i class="bx bx-calendar-event"></i><span>Pergerakan</span>
                <i class="bx bx-chevron-down ms-auto" x-bind:style="o && 'transform:rotate(180deg)'"></i>
            </a>
            <ul class="app-nav-sub" x-show="o" x-collapse>
                @can('pergerakan.view')
                    {{-- Padanan tepat: prefix 'pergerakan' bertindih dengan setiap submenu lain. --}}
                    <li><a class="app-nav-link {{ $path === 'pergerakan' ? 'active' : '' }}" href="{{ route('pergerakan.calendar') }}"><span>Jadual Bulanan</span></a></li>
                    <li><a class="app-nav-link {{ $path === 'pergerakan/senarai' ? 'active' : '' }}" href="{{ route('pergerakan.index') }}"><span>Senarai Pergerakan</span></a></li>
                @endcan
                @can('pergerakan.create')
                    <li><a class="app-nav-link {{ $path === 'pergerakan/saya' ? 'active' : '' }}" href="{{ route('pergerakan.my') }}"><span>Pergerakan Saya</span></a></li>
                @endcan
                @can('pergerakan.manageRoster')
                    <li class="app-sidebar__title">Selenggara</li>
                    <li><a class="app-nav-link {{ $path === 'pergerakan/pegawai-dipantau' ? 'active' : '' }}" href="{{ route('pergerakan.roster') }}"><span>Pegawai Dipantau</span></a></li>
                    <li><a class="app-nav-link {{ $path === 'pergerakan/jenis' ? 'active' : '' }}" href="{{ route('pergerakan.types') }}"><span>Jenis Pergerakan</span></a></li>
                @endcan
            </ul>
        </div>
    @endcanany

    @can('view.report')
      <div x-data="{ o: {{ str_starts_with($path, 'laporan') ? 'true' : 'false' }} }">
        <a class="app-nav-link" x-on:click="o = !o" role="button">
            <i class="bx bxs-report"></i><span>Laporan</span>
            <i class="bx bx-chevron-down ms-auto" x-bind:style="o && 'transform:rotate(180deg)'"></i>
        </a>
        <ul class="app-nav-sub" x-show="o" x-collapse>
            <li><a class="app-nav-link {{ str_starts_with($path, 'laporan') ? 'active' : '' }}" href="{{ url('/laporan/keseluruhan') }}"><span>Laporan Aduan   </span></a></li>
        </ul>
        {{-- <a class="app-nav-link {{ str_starts_with($path, 'laporan') ? 'active' : '' }}" href="{{ url('/laporan') }}">
            <i class="bx bxs-report"></i><span>Laporan</span>
        </a> --}}
      </div>
    @endcan

    {{-- Pentadbiran --}}
    @canany(['manage.division','manage.category', 'manage.admin', 'manage.role'])
        <div class="app-sidebar__title">Pentadbiran</div>
    @endcanany

    @canany(['manage.division','manage.category'])
        <div x-data="{ o: {{ (str_starts_with($path, 'kategori') || str_starts_with($path, 'status') || str_starts_with($path, 'aras-risiko')) ? 'true' : 'false' }} }">
            <a class="app-nav-link" x-on:click="o = !o" role="button">
                <i class="bx bx-cog"></i><span>Selenggaraan</span>
                <i class="bx bx-chevron-down ms-auto" x-bind:style="o && 'transform:rotate(180deg)'"></i>
            </a>
            <ul class="app-nav-sub" x-show="o" x-collapse>
                @can('manage.category')
                    <li><a class="app-nav-link {{ str_starts_with($path, 'kategori') ? 'active' : '' }}" href="{{ route('category.index') }}"><span>Kategori Aduan</span></a></li>
                    <li><a class="app-nav-link {{ str_starts_with($path, 'status') ? 'active' : '' }}" href="{{ route('status.index') }}"><span>Status Aduan</span></a></li>
                    <li><a class="app-nav-link {{ str_starts_with($path, 'aras-risiko') ? 'active' : '' }}" href="{{ route('risk-level.index') }}"><span>Tahap Risiko Aduan</span></a></li>
                @endcan
            </ul>
        </div>
    @endcanany

    @canany(['manage.user','manage.admin'])
        <div x-data="{ o: {{ str_starts_with($path, 'pengguna') ? 'true' : 'false' }} }">
            <a class="app-nav-link" x-on:click="o = !o" role="button">
                <i class="bx bxs-group"></i><span>Pengurusan Pengguna</span>
                <i class="bx bx-chevron-down ms-auto" x-bind:style="o && 'transform:rotate(180deg)'"></i>
            </a>
            <ul class="app-nav-sub" x-show="o" x-collapse>
                @can('manage.admin')
                    <li><a class="app-nav-link {{ $path === 'pengguna/pentadbir' ? 'active' : '' }}" href="{{ url('/pengguna/pentadbir') }}"><span>Pentadbir Helpdesk</span></a></li>
                @endcan
                @can('manage.user')
                    <li><a class="app-nav-link {{ $path === 'pengguna/pengurusan' ? 'active' : '' }}" href="{{ url('/pengguna/pengurusan') }}"><span>Pengurusan</span></a></li>
                    <li><a class="app-nav-link {{ $path === 'pengguna/peg-teknikal' ? 'active' : '' }}" href="{{ url('/pengguna/peg-teknikal') }}"><span>Peg. Teknikal</span></a></li>
                    {{-- Padanan tepat: prefix 'pengguna' akan bertindih dengan tiga submenu di atas. --}}
                    <li><a class="app-nav-link {{ $path === 'pengguna' ? 'active' : '' }}" href="{{ url('/pengguna') }}"><span>Pengguna</span></a></li>
                @endcan
            </ul>
        </div>
    @endcanany

    @can('manage.role')
        <a class="app-nav-link {{ str_starts_with($path, 'peranan') ? 'active' : '' }}" href="{{ url('/peranan') }}">
            <i class="bx bxs-user-badge"></i><span>Pengurusan Peranan</span>
        </a>
    @endcan

    {{-- Tetapan --}}
    @canany(['manage.audit-trail', 'manage.backup', 'manage.role'])
        <div class="app-sidebar__title">Tetapan</div>
        @can('manage.role')
            <a class="app-nav-link {{ str_contains($path, 'tetapan/notifikasi') ? 'active' : '' }}" href="{{ route('settings.notifications') }}">
                <i class="bx bx-bell"></i><span>Tetapan Notifikasi</span>
            </a>
        @endcan
        @can('manage.audit-trail')
            <a class="app-nav-link {{ str_starts_with($path, 'audit-trail') ? 'active' : '' }}" href="{{ url('/audit-trail') }}">
                <i class="bx bx-code-alt"></i><span>Log Sistem</span>
            </a>
        @endcan
        @can('manage.backup')
            <a class="app-nav-link {{ str_starts_with($path, 'backup') ? 'active' : '' }}" href="{{ url('/backup') }}">
                <i class="bx bx-data"></i><span>Backup</span>
            </a>
        @endcan
    @endcanany

    {{-- Manual --}}
    <div class="app-sidebar__title">Manual</div>
    <div x-data="{ o: false }">
        <a class="app-nav-link" x-on:click="o = !o" role="button">
            <i class="bx bxs-book-content"></i><span>Manual Pengguna</span>
            <i class="bx bx-chevron-down ms-auto" x-bind:style="o && 'transform:rotate(180deg)'"></i>
        </a>
        <ul class="app-nav-sub" x-show="o" x-collapse>
            {{-- Setiap manual ialah fail statik yang dibuka dalam tab baharu, jadi syarat
                 active dibandingkan dengan laluan fail yang dipautkan. --}}
            <li><a class="app-nav-link {{ $path === 'manual_pengguna/pengguna.pdf' ? 'active' : '' }}" href="{{ asset('manual_pengguna/pengguna.pdf') }}" target="_blank"><span>Pengguna</span></a></li>
            @can('aduan.updateTask')
                <li><a class="app-nav-link {{ $path === 'manual_pengguna/peg-teknikal.pdf' ? 'active' : '' }}" href="{{ asset('manual_pengguna/peg-teknikal.pdf') }}" target="_blank"><span>Peg. Teknikal</span></a></li>
            @endcan
            @can('aduan.adminTask')
                <li><a class="app-nav-link {{ $path === 'manual_pengguna/admin.pdf' ? 'active' : '' }}" href="{{ asset('manual_pengguna/admin.pdf') }}" target="_blank"><span>Admin Helpdesk</span></a></li>
            @endcan
            @hasrole('superadmin')
                <li><a class="app-nav-link {{ $path === 'manual_pengguna/superadmin.pdf' ? 'active' : '' }}" href="{{ asset('manual_pengguna/superadmin.pdf') }}" target="_blank"><span>Superadmin</span></a></li>
            @endhasrole
        </ul>
    </div>

</nav>
