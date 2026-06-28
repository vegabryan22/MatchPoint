<?php

namespace App\Enums;

enum BracketType: string
{
    case Main = 'main';
    case Losers = 'losers';
    case Finals = 'finals';
    case Group = 'group';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Llave principal',
            self::Losers => 'Llave de perdedores',
            self::Finals => 'Finales',
            self::Group => 'Fase de grupos',
        };
    }
}
