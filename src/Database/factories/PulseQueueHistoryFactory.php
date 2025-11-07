<?php

namespace Biigle\PulseQueueSizeCard\Database\factories;

use Biigle\PulseQueueSizeCard\PulseQueueHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PulseQueueHistoryFactory extends Factory
{
    protected $model = PulseQueueHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'queue' => "database:" . $this->faker->name(),
            'values' => json_encode([
                'delayed' => $this->faker->randomNumber(),
                'pending' => $this->faker->randomNumber(),
                'reserved' => $this->faker->randomNumber(),
            ]),
        ];
    }
}
