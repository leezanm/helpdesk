<div class="dropdown d-inline-block" wire:poll.30s>
    <button class="btn noti-icon position-relative" type="button"
            id="page-header-notifications-dropdown"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="bi bi-bell"></i>
        @if ($count > 0)
            <span class="noti-badge">{{ $count > 99 ? '99+' : $count }}</span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-end noti-menu p-0" aria-labelledby="page-header-notifications-dropdown">
        <div class="noti-menu__head">
            <span class="fw-semibold">Notifikasi</span>
            @if ($count > 0)
                <span class="badge rounded-pill" style="background:var(--c-primary-050);color:var(--c-primary-700)">{{ $count }} baharu</span>
            @endif
        </div>

        <div class="noti-menu__list">
            @forelse ($items as $item)
                <a href="#" class="noti-item" wire:key="noti-{{ $item['id'] }}"
                   wire:click.prevent="open('{{ $item['id'] }}')">
                    <span class="noti-item__icon {{ $item['color'] }}">
                        <i class="{{ $item['icon'] }}"></i>
                    </span>
                    <span class="noti-item__body">
                        <span class="noti-item__title">{{ $item['title'] }}</span>
                        <span class="noti-item__msg">{{ $item['message'] }}</span>
                        <span class="noti-item__time"><i class="bi bi-clock me-1"></i>{{ $item['time'] }}</span>
                    </span>
                </a>
            @empty
                <div class="noti-empty">
                    <i class="bi bi-bell-slash"></i>
                    <span>Tiada notifikasi baharu.</span>
                </div>
            @endforelse
        </div>

        @if ($count > 0)
            <button type="button" class="noti-menu__foot" wire:click.prevent="markAllRead">
                <i class="bi bi-check2-all me-1"></i> Tanda semua telah dibaca
            </button>
        @endif
    </div>
</div>
