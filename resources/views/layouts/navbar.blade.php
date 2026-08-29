@php $path = request()->path(); @endphp
<nav class="app-sidebar__nav">

    <div class="app-sidebar__title">Laman Utama</div>
    <a class="app-nav-link {{ $path === 'dashboard' ? 'active' : '' }}" href="{{ url('/dashboard') }}">
        <i class="bx bx-home-circle"></i><span>Utama</span>
    </a>

    {{-- Aduan --}}
    <div x-data="{ o: {{ str_starts_with($path, 'aduan') ? 'true' : 'false' }} }">
        <a class="app-nav-link" x-on:click="o = !o" role="button">
            <i class="bx bx-file-blank"></i><span>Aduan</span>
            <i class="bx bx-chevron-down ms-auto" x-bind:style="o && 'transform:rotate(180deg)'"></i>
        </a>
        <ul class="app-nav-sub" x-show="o" x-collapse>
            <li><a class="app-nav-link" href="{{ url('/aduan/create') }}"><span>Cipta Aduan</span></a></li>
            <li><a class="app-nav-link" href="{{ url('/aduan/user') }}/{{ Auth::user()->username }}"><span>Aduan Saya</span></a></li>

            @can('aduan.updateTask')
                <li class="app-sidebar__title">Peg Teknikal</li>
                <li><a class="app-nav-link" href="{{ url('/aduan/untuk-tindakan') }}"><span>Untuk Tindakan</span></a></li>
            @endcan

            @can('aduan.adminTask')
                <li class="app-sidebar__title">Pentadbir Helpdesk</li>
                <li><a class="app-nav-link" href="{{ url('/aduan/dihantar') }}"><span>Aduan Dihantar</span></a></li>
            @endcan

            @canany(['aduan.adminTask','aduan.updateTask'])
                <li><a class="app-nav-link" href="{{ url('/aduan') }}"><span>Senarai Aduan</span></a></li>
            @endcanany
        </ul>
    </div>

    @can('view.report')
        <a class="app-nav-link {{ str_starts_with($path, 'laporan') ? 'active' : '' }}" href="{{ url('/laporan') }}">
            <i class="bx bxs-report"></i><span>Laporan</span>
        </a>
    @endcan

    {{-- Pentadbiran --}}
    @canany(['manage.division','manage.category', 'manage.admin', 'manage.role'])
        <div class="app-sidebar__title">Pentadbiran</div>
    @endcanany

    @canany(['manage.division','manage.category'])
        <div x-data="{ o: {{ (str_starts_with($path, 'kategori') || str_starts_with($path, 'status')) ? 'true' : 'false' }} }">
            <a class="app-nav-link" x-on:click="o = !o" role="button">
                <i class="bx bx-cog"></i><span>Selenggaraan</span>
                <i class="bx bx-chevron-down ms-auto" x-bind:style="o && 'transform:rotate(180deg)'"></i>
            </a>
            <ul class="app-nav-sub" x-show="o" x-collapse>
                @can('manage.category')
                    <li><a class="app-nav-link {{ str_starts_with($path, 'kategori') ? 'active' : '' }}" href="{{ route('category.index') }}"><span>Kategori Aduan</span></a></li>
                    <li><a class="app-nav-link {{ str_starts_with($path, 'status') ? 'active' : '' }}" href="{{ route('status.index') }}"><span>Status Aduan</span></a></li>
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
                    <li><a class="app-nav-link" href="{{ url('/pengguna/pentadbir') }}"><span>Pentadbir Helpdesk</span></a></li>
                @endcan
                @can('manage.user')
                    <li><a class="app-nav-link" href="{{ url('/pengguna/pengurusan') }}"><span>Pengurusan</span></a></li>
                    <li><a class="app-nav-link" href="{{ url('/pengguna/peg-teknikal') }}"><span>Peg. Teknikal</span></a></li>
                    <li><a class="app-nav-link" href="{{ url('/pengguna') }}"><span>Pengguna</span></a></li>
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
    @canany(['manage.audit-trail', 'manage.backup'])
        <div class="app-sidebar__title">Tetapan</div>
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
            <li><a class="app-nav-link" href="{{ asset('manual_pengguna/pengguna.pdf') }}" target="_blank"><span>Pengguna</span></a></li>
            @can('aduan.updateTask')
                <li><a class="app-nav-link" href="{{ asset('manual_pengguna/peg-teknikal.pdf') }}" target="_blank"><span>Peg. Teknikal</span></a></li>
            @endcan
            @can('aduan.adminTask')
                <li><a class="app-nav-link" href="{{ asset('manual_pengguna/admin.pdf') }}" target="_blank"><span>Admin Helpdesk</span></a></li>
            @endcan
            @hasrole('superadmin')
                <li><a class="app-nav-link" href="{{ asset('manual_pengguna/superadmin.pdf') }}" target="_blank"><span>Superadmin</span></a></li>
            @endhasrole
        </ul>
    </div>

</nav>
