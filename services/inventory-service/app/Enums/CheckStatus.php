<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Résultat du contrôle physique d'une ligne d'inventaire.
 */
enum CheckStatus: string
{
    case Pending = 'pending';
    case Found = 'found';
    case Missing = 'missing';
    case Damaged = 'damaged';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Un écart = bien non retrouvé ou endommagé. */
    public function isDiscrepancy(): bool
    {
        return $this === self::Missing || $this === self::Damaged;
    }
}
