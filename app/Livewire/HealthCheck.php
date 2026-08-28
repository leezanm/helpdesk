<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Temporary coexistence check for task 7.2.
 *
 * Proves Livewire server-side reactivity works inside the existing Blade
 * layout alongside Bootstrap 5 and Alpine.js. Removed once verified.
 */
class HealthCheck extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render()
    {
        return view('livewire.health-check');
    }
}
