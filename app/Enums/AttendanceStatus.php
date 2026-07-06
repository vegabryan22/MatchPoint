<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Pending = 'pending';
    case Present = 'present';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Present => 'Presente',
            self::Absent => 'Ausente',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-secondary',
            self::Present => 'text-bg-success',
            self::Absent => 'text-bg-danger',
        };
    }
}
