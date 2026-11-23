<?php

namespace Database\Factories;

use App\Models\MonitoredOfficer;
use App\Models\MovementUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoredOfficerFactory extends Factory
{
    protected $model = MonitoredOfficer::class;

    public function definition(): array
    {
        return [
            'movement_unit_id' => MovementUnit::factory(),
            'user_id' => User::factory(),
            'display_name' => 'En. '.$this->faker->name(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Pegawai roster bagi pengguna tertentu, nama paparan ikut nama akaun. */
    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
            'display_name' => $user->name,
        ]);
    }
}
