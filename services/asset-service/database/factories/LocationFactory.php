<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city().' — '.fake()->streetName(),
            'code' => 'LOC-'.Str::upper(Str::random(6)),
            'type' => fake()->randomElement(['site', 'bâtiment', 'étage', 'salle']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
