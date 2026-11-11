<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Workflow d'une demande de maintenance.
 * requested -> scheduled -> in_progress -> completed
 */
enum MaintenanceStatus: string
{
    case Requested = 'requested';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandée',
            self::Scheduled => 'Planifiée',
            self::InProgress => 'En cours',
            self::Completed => 'Terminée',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Requested => $target === self::Scheduled,
            self::Scheduled => $target === self::InProgress,
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
