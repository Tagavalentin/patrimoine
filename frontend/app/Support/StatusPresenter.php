<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Présentation des statuts métier (libellés FR + classes badge Tailwind).
 * Les valeurs viennent des enums des microservices — on mappe ici
 * pour l'affichage, sans dupliquer la logique métier.
 */
class StatusPresenter
{
    private const ASSET = [
        'acquired' => ['label' => 'Acquis', 'class' => 'bg-emerald-100 text-emerald-800'],
        'assigned' => ['label' => 'Affecté', 'class' => 'bg-blue-100 text-blue-800'],
        'in_maintenance' => ['label' => 'En maintenance', 'class' => 'bg-amber-100 text-amber-800'],
        'decommissioned' => ['label' => 'Réformé', 'class' => 'bg-orange-100 text-orange-800'],
        'disposed' => ['label' => 'Cédé', 'class' => 'bg-slate-200 text-slate-700'],
    ];

    private const MAINTENANCE = [
        'requested' => ['label' => 'Demandée', 'class' => 'bg-blue-100 text-blue-800'],
        'scheduled' => ['label' => 'Planifiée', 'class' => 'bg-indigo-100 text-indigo-800'],
        'in_progress' => ['label' => 'En cours', 'class' => 'bg-amber-100 text-amber-800'],
        'completed' => ['label' => 'Terminée', 'class' => 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['label' => 'Annulée', 'class' => 'bg-slate-200 text-slate-700'],
    ];

    private const INVENTORY = [
        'draft' => ['label' => 'Brouillon', 'class' => 'bg-slate-200 text-slate-700'],
        'in_progress' => ['label' => 'En cours', 'class' => 'bg-amber-100 text-amber-800'],
        'completed' => ['label' => 'Terminée', 'class' => 'bg-emerald-100 text-emerald-800'],
    ];

    private const DISPOSAL = [
        'requested' => ['label' => 'Demandée', 'class' => 'bg-blue-100 text-blue-800'],
        'approved' => ['label' => 'Approuvée', 'class' => 'bg-indigo-100 text-indigo-800'],
        'completed' => ['label' => 'Réalisée', 'class' => 'bg-emerald-100 text-emerald-800'],
        'rejected' => ['label' => 'Rejetée', 'class' => 'bg-red-100 text-red-800'],
    ];

    /**
     * @return array{label: string, class: string}
     */
    public static function get(string $domain, ?string $value): array
    {
        $map = match ($domain) {
            'asset' => self::ASSET,
            'maintenance' => self::MAINTENANCE,
            'inventory' => self::INVENTORY,
            'disposal' => self::DISPOSAL,
            default => [],
        };

        return $map[$value ?? ''] ?? ['label' => 'Inconnu', 'class' => 'bg-slate-200 text-slate-700'];
    }

    /**
     * @return array{label: string, class: string}
     */
    public static function asset(?string $status): array
    {
        return self::get('asset', $status);
    }

    /**
     * @return array{label: string, class: string}
     */
    public static function maintenance(?string $status): array
    {
        return self::get('maintenance', $status);
    }

    /**
     * @return array{label: string, class: string}
     */
    public static function inventory(?string $status): array
    {
        return self::get('inventory', $status);
    }
}
