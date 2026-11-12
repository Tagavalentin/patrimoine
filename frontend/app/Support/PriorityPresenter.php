<?php

declare(strict_types=1);

namespace App\Support;

class PriorityPresenter
{
    /**
     * @return array{label: string, class: string}
     */
    public static function get(?string $priority): array
    {
        return match ($priority) {
            'low' => ['label' => 'Basse', 'class' => 'bg-slate-100 text-slate-700'],
            'normal' => ['label' => 'Normale', 'class' => 'bg-blue-100 text-blue-800'],
            'high' => ['label' => 'Haute', 'class' => 'bg-amber-100 text-amber-800'],
            'urgent' => ['label' => 'Urgente', 'class' => 'bg-red-100 text-red-800'],
            default => ['label' => '—', 'class' => 'bg-slate-100 text-slate-500'],
        };
    }
}
