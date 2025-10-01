<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChargeDetail>
 */
class ChargeDetailFactory extends Factory
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
            "weight"=> $this->faker->randomFloat(2, 0.1, 100),
            "price"=> $this->faker->randomFloat(2, 1, 100),
            "unit_price"=> $this->faker->randomFloat(2, 1, 100),
            "price"=> $this->faker->randomFloat(2, 1, 100),
            "package_count"=> $this->faker->randomNumber(2),
        ];
    }
}
