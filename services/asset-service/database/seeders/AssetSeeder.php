<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();

        $designations = [
            ['Ordinateur portable Dell Latitude', 'INF', 'HQ-A', 1250000, 5],
            ['Serveur de production HP ProLiant', 'INF', 'HQ-SRV', 8500000, 5],
            ['Imprimante laser multifonction', 'INF', 'HQ-B', 350000, 4],
            ['Véhicule de service Toyota Hilux', 'VEH', 'HQ-PARK', 18500000, 8],
            ['Véhicule utilitaire Renault Master', 'VEH', 'HQ-PARK', 14200000, 8],
            ['Bureau directional en teck', 'MOB', 'HQ-A', 280000, 10],
            ['Chaise de bureau ergonomique', 'MOB', 'HQ-B', 95000, 5],
            ['Table de réunion 12 places', 'MOB', 'HQ-A', 450000, 10],
            ['Échographe portable', 'SCI', 'HQ-B', 6200000, 7],
            ['Autoclave de stérilisation', 'SCI', 'HQ-SRV', 2400000, 6],
            ['Groupe électrogène 100 kVA', 'IMM', 'HQ-A', 15800000, 12],
            ['Onduleur industriel 30 kVA', 'IMM', 'HQ-SRV', 7200000, 8],
            ['Bâtiment administratif annexe', 'IMM', 'REG-N', 250000000, 30],
            ['Tablettes graphiques Wacom', 'INF', 'HQ-B', 410000, 4],
            ['Projecteur multimédia', 'INF', 'HQ-A', 680000, 4],
        ];

        $statuses = [
            AssetStatus::Acquired, AssetStatus::Acquired, AssetStatus::Assigned,
            AssetStatus::Assigned, AssetStatus::InMaintenance, AssetStatus::Acquired,
            AssetStatus::Assigned, AssetStatus::Decommissioned, AssetStatus::Acquired,
            AssetStatus::Acquired, AssetStatus::Disposed, AssetStatus::Assigned,
            AssetStatus::Acquired, AssetStatus::Acquired, AssetStatus::InMaintenance,
        ];

        foreach ($designations as $i => [$designation, $catCode, $locCode, $value, $life]) {
            Asset::factory()->create([
                'designation' => $designation,
                'category_id' => Category::where('code', $catCode)->value('id'),
                'location_id' => Location::where('code', $locCode)->value('id'),
                'department_id' => $departments->isNotEmpty() ? $departments->random()->id : null,
                'acquisition_value' => $value,
                'useful_life_years' => $life,
                'acquisition_date' => now()->subYears(fake()->numberBetween(1, 6))->subMonths(fake()->numberBetween(0, 11)),
                'status' => $statuses[$i] ?? AssetStatus::Acquired,
                'description' => "Bien de démonstration : {$designation}",
            ]);
        }
    }
}
