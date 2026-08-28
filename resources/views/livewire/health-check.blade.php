<div class="card">
    <div class="card-body">
        <h5 class="card-title">Livewire Health Check</h5>

        {{-- Livewire server-side reactivity (wire:click) --}}
        <p>Livewire counter: <span class="badge bg-primary" id="lw-count">{{ $count }}</span></p>
        <button type="button" class="btn btn-primary btn-sm" wire:click="increment">
            Increment (Livewire)
        </button>

        {{-- Alpine.js (bundled with Livewire 3) --}}
        <div x-data="{ open: false }" class="mt-3">
            <button type="button" class="btn btn-secondary btn-sm" x-on:click="open = !open">
                Toggle (Alpine)
            </button>
            <span x-show="open" class="ms-2 text-success" id="alpine-msg">Alpine is working.</span>
        </div>
    </div>
</div>
