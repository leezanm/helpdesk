<?php

namespace Database\Factories;

use App\Models\MovementHoliday;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovementHolidayFactory extends Factory
{
    protected $model = MovementHoliday::class;

    public function definition(): array
    {
        $date = now()->addDays(10)->toDateString();

        return [
            'name' => 'Cuti '.$this->faker->unique()->word(),
            'start_date' => $date,
            'end_date' => $date,
            'movement_unit_id' => null,   // null = semua unit
        ];
    }

    public function onDate(string $date): static
    {
        return $this->state(['start_date' => $date, 'end_date' => $date]);
    }
}
