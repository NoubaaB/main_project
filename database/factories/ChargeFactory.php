<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Charge>
 */
class ChargeFactory extends Factory
{
    function tracking_numbers(): array
    {
        $length = rand(1, 5); // number of elements between 1 and 5

        $numbers = [];
        for ($i = 0; $i < $length; $i++) {
            $numbers[] = (string) rand(1000, 9999999); // random number then cast to string
        }

        return $numbers;
    }
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            "date" => $this->faker->dateTimeBetween('-2 month', 'now'),
            "tracking_numbers" => json_encode($this->tracking_numbers()),
            // "total_price" => $this->faker->randomFloat(2, 100, 1000),
            // "total_weight" => $this->faker->randomFloat(2, 10, 100),
        ];
    }
}
