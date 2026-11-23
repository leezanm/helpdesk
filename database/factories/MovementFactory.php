<?php

namespace Database\Factories;

use App\Models\MonitoredOfficer;
use App\Models\Movement;
use App\Models\Ref\MovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovementFactory extends Factory
{
    protected $model = Movement::class;

    public function definition(): array
    {
        $start = now()->addDays(3)->toDateString();

        return [
            'monitored_officer_id' => MonitoredOfficer::factory(),
            'movement_type_id' => MovementType::factory(),
            'title' => $this->faker->sentence(4),
            'start_date' => $start,
            'end_date' => $start,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'location' => 'Bilik Mesyuarat Digitalis, Aras 11',
            'is_outside' => false,
            'remarks' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /** Entri yang merentasi beberapa hari. */
    public function spanning(string $start, string $end): static
    {
        return $this->state(['start_date' => $start, 'end_date' => $end]);
    }

    /** Aktiviti di luar pejabat — pegawai tidak boleh dicari di pejabat. */
    public function outside(): static
    {
        return $this->state(['is_outside' => true, 'location' => 'Putrajaya']);
    }

    /** Entri sehari pada tarikh tertentu. */
    public function onDate(string $date): static
    {
        return $this->state(['start_date' => $date, 'end_date' => $date]);
    }
}
