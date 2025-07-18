<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
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
            "name"=> $this->faker->company(),
            "ice"=> $this->faker->unique()->numerify('ICE-########'),
            "country"=> $this->faker->country(),
            "city"=> $this->faker->city(),
            "address"=> $this->faker->address(),
            "tel"=> $this->faker->phoneNumber(),
            "logo"=> $this->faker->imageUrl(640, 480, 'business', true, 'Provider'),
            "state"=> $this->faker->boolean(90) //
        ];
    }
}
