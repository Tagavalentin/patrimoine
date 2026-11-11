<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DisposalStatus;
use App\Enums\DisposalType;
use App\Models\Asset;
use App\Models\Disposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Disposal> */
class DisposalFactory extends Factory
{
    protected $model = Disposal::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'reference' => 'DSP-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'disposal_type' => DisposalType::Sale,
            'reason' => fake()->sentence(),
            'estimated_value' => fake()->numberBetween(10000, 5000000),
            'status' => DisposalStatus::Requested,
            'requested_by' => 1,
            'requested_by_name' => fake()->name(),
            'requested_by_email' => fake()->safeEmail(),
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => DisposalStatus::Approved,
            'approved_by' => 1,
            'approved_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => DisposalStatus::Completed,
            'approved_by' => 1,
            'approved_at' => now()->subDay(),
            'completed_by' => 1,
            'completed_at' => now(),
            'disposal_date' => now()->toDateString(),
        ]);
    }
}
