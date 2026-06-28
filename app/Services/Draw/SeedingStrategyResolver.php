<?php

namespace App\Services\Draw;

use App\Contracts\SeedingStrategyInterface;
use App\Enums\DrawMethod;
use InvalidArgumentException;

final class SeedingStrategyResolver
{
    /** @var array<string, SeedingStrategyInterface> */
    private array $strategies;

    public function __construct(
        RandomSeedingStrategy $random,
        AutomaticSeedingStrategy $automatic,
        ManualSeedingStrategy $manual,
    ) {
        $this->strategies = [
            $random->method()->value => $random,
            $automatic->method()->value => $automatic,
            $manual->method()->value => $manual,
        ];
    }

    public function resolve(DrawMethod $method): SeedingStrategyInterface
    {
        return $this->strategies[$method->value]
            ?? throw new InvalidArgumentException("Estrategia no registrada: {$method->value}");
    }
}
