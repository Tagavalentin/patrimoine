<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\InventoryCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryCampaign> */
class InventoryCampaignFactory extends Factory
{
    protected $model = InventoryCampaign::class;

    public function definition(): array
    {
        return [
            'reference' => 'INV-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => 'Inventaire '.fake()->city(),
            'status' => CampaignStatus::Draft,
            'created_by' => 1,
            'created_by_name' => fake()->name(),
            'created_by_email' => fake()->safeEmail(),
        ];
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => CampaignStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => CampaignStatus::Completed,
            'started_at' => now()->subDays(3),
            'completed_at' => now(),
        ]);
    }
}
