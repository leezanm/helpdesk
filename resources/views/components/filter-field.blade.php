@props([
    'label' => null,
    'col' => 'col-12 col-md-3',   // responsive width
])

{{-- A single labelled control inside <x-filter-bar>. --}}
<div class="{{ $col }} filter-field">
    @if ($label)
        <label class="form-label small mb-1">{{ $label }}</label>
    @endif
    {{ $slot }}
</div>
