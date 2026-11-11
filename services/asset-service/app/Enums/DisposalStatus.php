<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Workflow de sortie de patrimoine (cession / déclassement).
 * requested -> approved -> completed ; requested -> rejected.
 */
enum DisposalStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandée',
            self::Approved => 'Approuvée',
            self::Rejected => 'Rejetée',
            self::Completed => 'Réalisée',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Requested => in_array($target, [self::Approved, self::Rejected], true),
            self::Approved => $target === self::Completed,
            self::Rejected, self::Completed => false,
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Requested, self::Approved], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
