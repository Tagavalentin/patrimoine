<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Department> */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'code' => 'DEP-'.Str::upper(Str::random(6)),
            'parent_id' => null,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function childOf(Department $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
