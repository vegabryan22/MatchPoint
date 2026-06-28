<?php

namespace App\Enums;

enum BestOf: int
{
    case One = 1;
    case Three = 3;
    case Five = 5;

    public function label(): string
    {
        return "Mejor de {$this->value}";
    }
}
