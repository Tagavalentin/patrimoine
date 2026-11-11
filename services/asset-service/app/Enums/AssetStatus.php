<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cycle de vie d'un bien — machine à états explicite.
 * Toute transition hors de la table TRANSITIONS est refusée.
 */
enum AssetStatus: string
{
    case Acquired = 'acquired';
    case Assigned = 'assigned';
    case InMaintenance = 'in_maintenance';
    case Decommissioned = 'decommissioned';
    case Disposed = 'disposed';

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'acquired' => ['assigned', 'in_maintenance', 'decommissioned', 'disposed'],
        'assigned' => ['acquired', 'in_maintenance', 'decommissioned'],
        'in_maintenance' => ['acquired', 'assigned', 'decommissioned'],
        'decommissioned' => ['disposed'],
        'disposed' => [],
    ];

    public function label(): string
    {
        return match ($this) {
            self::Acquired => 'Acquis',
            self::Assigned => 'Affecté',
            self::InMaintenance => 'En maintenance',
            self::Decommissioned => 'Réformé',
            self::Disposed => 'Cédé / sorti',
        };
    }

    /** @return list<AssetStatus> */
    public function allowedTransitions(): array
    {
        return array_map(
            fn (string $status): self => self::from($status),
            self::TRANSITIONS[$this->value],
        );
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::TRANSITIONS[$this->value], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
