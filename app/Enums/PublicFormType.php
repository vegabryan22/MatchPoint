<?php

namespace App\Enums;

use App\Models\Tournament;

enum PublicFormType: string
{
    case QuickRegistration = 'quick-registration';

    public function label(): string
    {
        return match ($this) {
            self::QuickRegistration => 'Inscripción pública',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::QuickRegistration => 'quick-registrations.create',
        };
    }

    public function isEnabled(Tournament $tournament): bool
    {
        return match ($this) {
            self::QuickRegistration => $tournament->quick_registration_enabled,
        };
    }
}
