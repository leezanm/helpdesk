<?php

namespace Tests\Feature\Livewire;

use App\Livewire\HealthCheck;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 7.2 coexistence check: proves the Livewire runtime is wired up and
 * server-side reactivity (wire:click -> action) works. Also establishes the
 * Livewire::test() pattern used for the screen conversions in tasks 8+.
 */
class HealthCheckTest extends TestCase
{
    public function test_component_renders()
    {
        Livewire::test(HealthCheck::class)
            ->assertOk()
            ->assertSee('Livewire Health Check')
            ->assertSet('count', 0);
    }

    public function test_increment_action_is_reactive()
    {
        Livewire::test(HealthCheck::class)
            ->assertSet('count', 0)
            ->call('increment')
            ->assertSet('count', 1)
            ->call('increment')
            ->assertSet('count', 2);
    }
}
