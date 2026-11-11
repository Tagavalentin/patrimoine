<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration des référentiels.
 */
class ReferentialSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedLocations();
        $this->seedDepartments();
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Immobilier', 'code' => 'IMM', 'description' => 'Bâtiments, terrains et infrastructures'],
            ['name' => 'Véhicule', 'code' => 'VEH', 'description' => 'Véhicules terrestres motorisés'],
            ['name' => 'Mobilier', 'code' => 'MOB', 'description' => 'Mobilier de bureau et d\'accueil'],
            ['name' => 'Informatique', 'code' => 'INF', 'description' => 'Matériel informatique et bureautique'],
            ['name' => 'Équipement scientifique', 'code' => 'SCI', 'description' => 'Équipements et instruments scientifiques'],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(['code' => $data['code']], $data);
        }
    }

    private function seedLocations(): void
    {
        $locations = [
            ['name' => 'Siège — Bâtiment A', 'code' => 'HQ-A', 'type' => 'bâtiment', 'address' => '1, rue de l\'Immeuble', 'city' => 'Dakar'],
            ['name' => 'Siège — Bâtiment B', 'code' => 'HQ-B', 'type' => 'bâtiment', 'address' => '3, rue de l\'Immeuble', 'city' => 'Dakar'],
            ['name' => 'Salle des serveurs', 'code' => 'HQ-SRV', 'type' => 'salle', 'address' => 'Sous-sol Bâtiment A', 'city' => 'Dakar'],
            ['name' => 'Antenne régionale Nord', 'code' => 'REG-N', 'type' => 'site', 'address' => 'Route nationale 1', 'city' => 'Saint-Louis'],
            ['name' => 'Antenne régionale Sud', 'code' => 'REG-S', 'type' => 'site', 'address' => 'Avenue de la Gare', 'city' => 'Ziguinchor'],
            ['name' => 'Parking couvert', 'code' => 'HQ-PARK', 'type' => 'site', 'address' => 'Arrière Bâtiment B', 'city' => 'Dakar'],
        ];

        foreach ($locations as $data) {
            Location::updateOrCreate(['code' => $data['code']], $data);
        }
    }

    private function seedDepartments(): void
    {
        $ministries = [
            ['name' => 'Ministère de l\'Économie et des Finances', 'code' => 'MEF'],
            ['name' => 'Ministère de l\'Intérieur', 'code' => 'MINT'],
            ['name' => 'Ministère de l\'Éducation nationale', 'code' => 'MEN'],
        ];

        $services = [
            'MEF' => ['Trésor Public', 'Direction du Patrimoine de l\'État', 'Contrôle Général des Finances'],
            'MINT' => ['Direction des Services Informatiques', 'Direction de l\'État Civil'],
            'MEN' => ['Direction des Affaires Administratives et Financières'],
        ];

        foreach ($ministries as $data) {
            Department::updateOrCreate(['code' => $data['code']], $data);
        }

        foreach ($services as $parentCode => $names) {
            $parent = Department::where('code', $parentCode)->firstOrFail();

            foreach ($names as $name) {
                $code = 'SVC-'.substr(md5($parentCode.$name), 0, 6);
                Department::updateOrCreate(
                    ['code' => $code],
                    ['name' => $name, 'parent_id' => $parent->id],
                );
            }
        }
    }
}
