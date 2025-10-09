<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
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
            "name" => $this->faker->company,
            "ice" => $this->faker->unique()->randomNumber(8),
            "email" => $this->faker->unique()->safeEmail,
            "address" => $this->faker->address,
            "city" => $this->faker->city,
            "country" => $this->faker->country,
            "postal_code" => $this->faker->postcode,
            "website" => $this->faker->url,
            "description" => $this->faker->text(200),
            "logo_image" => $this->faker->imageUrl(640, 480, 'business', true),
            "active_status" => $this->faker->boolean,
            "phone" => $this->faker->phoneNumber
        ];
    }
}
