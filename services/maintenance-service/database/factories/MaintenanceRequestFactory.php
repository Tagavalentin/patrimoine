<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintenanceRequest> */
class MaintenanceRequestFactory extends Factory
{
    protected $model = MaintenanceRequest::class;

    public function definition(): array
    {
        return [
            'reference' => 'MNT-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'asset_id' => fake()->unique()->numberBetween(1, 99999),
            'asset_code' => 'AST-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'asset_designation' => fake()->sentence(4),
            'asset_status' => 'acquired',
            'description' => fake()->sentence(),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => MaintenanceStatus::Requested,
            'requested_by' => 1,
            'requested_by_name' => fake()->name(),
            'requested_by_email' => fake()->safeEmail(),
        ];
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_date' => now()->addWeek()->toDateString(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => MaintenanceStatus::InProgress,
            'scheduled_date' => now()->subDay()->toDateString(),
            'started_at' => now(),
            'asset_previous_status' => 'acquired',
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => MaintenanceStatus::Completed,
            'scheduled_date' => now()->subDays(5)->toDateString(),
            'started_at' => now()->subDays(4),
            'completed_at' => now(),
        ]);
    }
}
