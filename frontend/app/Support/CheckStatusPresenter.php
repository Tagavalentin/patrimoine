<?php

declare(strict_types=1);

namespace App\Support;

class CheckStatusPresenter
{
    private const MAP = [
        'pending' => ['label' => 'En attente', 'class' => 'bg-slate-100 text-slate-600'],
        'found' => ['label' => 'Trouvé', 'class' => 'bg-emerald-100 text-emerald-800'],
        'missing' => ['label' => 'Manquant', 'class' => 'bg-red-100 text-red-800'],
        'damaged' => ['label' => 'Endommagé', 'class' => 'bg-amber-100 text-amber-800'],
    ];

    /**
     * @return array{label: string, class: string}
     */
    public static function get(?string $status): array
    {
        return self::MAP[$status ?? ''] ?? ['label' => 'Inconnu', 'class' => 'bg-slate-100 text-slate-500'];
    }
}
