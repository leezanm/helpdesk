<div class="dropdown d-inline-block" wire:poll.30s>
    <button class="btn header-item noti-icon waves-effect" id="page-header-notifications-dropdown"
        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="bx bx-bell bx-tada"></i>
        <span id="notiCount" class="badge bg-danger rounded-pill">{{ $count }}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">
        <div class="px-3 py-2 bg-primary bg-soft">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0" key="t-notifications"> Notifikasi </h6>
                </div>
            </div>
        </div>
        <div id="notiList" data-simplebar style="max-height: 410px;">
            @forelse ($items as $item)
                <a href="#" class="text-reset notification-item" wire:key="noti-{{ $item['id'] }}"
                   wire:click.prevent="open('{{ $item['id'] }}')">
                    <div class="media">
                        <div class="avatar-xs me-3">
                            <span class="avatar-title {{ $item['color'] }} rounded-circle font-size-16 align-middle">
                                <i class="{{ $item['icon'] }} align-middle"></i>
                            </span>
                        </div>
                        <div class="media-body">
                            <h6 class="mt-0 mb-1">{{ $item['title'] }}</h6>
                            <div class="font-size-12 text-muted">
                                <p class="mb-1">{{ $item['message'] }}</p>
                                <p class="mb-0"><i class="mdi mdi-clock-outline"></i>
                                    <span>{{ $item['time'] }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-3 text-center text-muted font-size-12">Tiada notifikasi baharu.</div>
            @endforelse
        </div>
    </div>
</div>
