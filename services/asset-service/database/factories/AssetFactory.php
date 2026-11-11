<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'code' => 'AST-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'designation' => fake()->sentence(4),
            'category_id' => Category::factory(),
            'location_id' => Location::factory(),
            'department_id' => null,
            'acquisition_date' => fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'acquisition_value' => fake()->randomFloat(2, 5000, 500000),
            'useful_life_years' => fake()->randomElement([3, 5, 8, 10]),
            'status' => AssetStatus::Acquired,
            'description' => fake()->optional()->paragraph(),
            'attachments' => null,
        ];
    }

    public function forDepartment(Department $department): static
    {
        return $this->state(['department_id' => $department->id]);
    }

    public function assigned(): static
    {
        return $this->state(['status' => AssetStatus::Assigned]);
    }

    public function inMaintenance(): static
    {
        return $this->state(['status' => AssetStatus::InMaintenance]);
    }

    public function decommissioned(): static
    {
        return $this->state(['status' => AssetStatus::Decommissioned]);
    }

    public function disposed(): static
    {
        return $this->state(['status' => AssetStatus::Disposed]);
    }
}
