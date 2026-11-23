<?php

namespace Database\Factories;

use App\Models\MovementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovementUnitFactory extends Factory
{
    protected $model = MovementUnit::class;

    public function definition(): array
    {
        return [
            'name' => 'Unit '.$this->faker->unique()->word(),
            'short_name' => null,
            'is_active' => true,
        ];
    }
}
