<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Motif de sortie du patrimoine.
 */
enum DisposalType: string
{
    case Sale = 'sale';                 // Cession / vente
    case Donation = 'donation';         // Don
    case Destruction = 'destruction';   // Destruction / mise au rebut
    case WrittenOff = 'written_off';    // Déclassement comptable

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Cession',
            self::Donation => 'Don',
            self::Destruction => 'Destruction',
            self::WrittenOff => 'Déclassement',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
