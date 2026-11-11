<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cycle de vie d'une campagne d'inventaire.
 * draft -> in_progress -> completed
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::InProgress => 'En cours',
            self::Completed => 'Clôturée',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => $target === self::InProgress,
            self::InProgress => $target === self::Completed,
            self::Completed => false,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
