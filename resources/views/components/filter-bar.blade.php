@props([
    'title' => 'Penapis',
    'icon' => 'bi-funnel',
    'showReset' => false,
    'resetAction' => 'resetFilters',
    'action' => null,   // optional slot for a primary action (e.g. "Baru" button)
])

{{--
    Standard filter/search bar used across all list & report screens.
    Card-wrapped, labelled controls in a responsive grid, matching the
    dashboard look. Put controls in the default slot (each wrapped in a
    .filter-field). Pass an $action slot for a primary button.
--}}
<div class="card filter-card mb-3">
    <div class="card-header filter-card__header">
        <h6 class="mb-0"><i class="bi {{ $icon }} me-1"></i> {{ $title }}</h6>
        <div class="d-flex align-items-center gap-2">
            @if ($showReset)
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="{{ $resetAction }}">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Set Semula
                </button>
            @endif
            @isset($action)
                {{ $action }}
            @endisset
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end filter-grid">
            {{ $slot }}
        </div>
    </div>
</div>
