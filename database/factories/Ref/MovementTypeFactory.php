<?php

namespace Database\Factories\Ref;

use App\Models\Ref\MovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovementTypeFactory extends Factory
{
    protected $model = MovementType::class;

    public function definition(): array
    {
        return [
            'name' => 'Jenis '.$this->faker->unique()->word(),
            'class' => 'bg-primary',
            'is_leave' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /** Jenis cuti — asas amaran tindih. */
    public function leave(): static
    {
        return $this->state(['is_leave' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
