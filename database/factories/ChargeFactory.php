<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Charge>
 */
class ChargeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            "date" => $this->faker->dateTimeBetween('-1 month', 'now'),
            "tracking_numbers" => json_encode($this->faker->randomElements(['123456789', '987654321', '456789123'], 2)),
            // "total_price" => $this->faker->randomFloat(2, 100, 1000),
            // "total_weight" => $this->faker->randomFloat(2, 10, 100),
            "is_stockable" => $this->faker->boolean,
        ];
    }
}
