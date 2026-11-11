<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CheckStatus;
use App\Models\InventoryLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryLine> */
class InventoryLineFactory extends Factory
{
    protected $model = InventoryLine::class;

    public function definition(): array
    {
        return [
            'inventory_campaign_id' => InventoryCampaignFactory::new(),
            'asset_id' => fake()->unique()->numberBetween(1, 99999),
            'asset_code' => 'AST-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'asset_designation' => fake()->sentence(4),
            'asset_status' => 'acquired',
            'department_name' => fake()->company(),
            'location_name' => fake()->city(),
            'check_status' => CheckStatus::Pending,
        ];
    }

    public function found(): static
    {
        return $this->state([
            'check_status' => CheckStatus::Found,
            'checked_at' => now(),
        ]);
    }

    public function missing(): static
    {
        return $this->state([
            'check_status' => CheckStatus::Missing,
            'notes' => 'Bien introuvable sur site',
            'checked_at' => now(),
        ]);
    }
}
