<?php

namespace App\Services\Brackets;

use App\Enums\TournamentFormat;
use Illuminate\Validation\ValidationException;

final class BracketGeneratorResolver
{
    /** @var list<BracketGeneratorInterface> */
    private array $generators;

    public function __construct(
        SingleEliminationBracketGenerator $singleElimination,
        DoubleEliminationBracketGenerator $doubleElimination,
    ) {
        $this->generators = [$singleElimination, $doubleElimination];
    }

    public function resolve(TournamentFormat $format): BracketGeneratorInterface
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($format)) {
                return $generator;
            }
        }

        throw ValidationException::withMessages(['draw' => 'El formato no tiene un generador de llaves disponible.']);
    }
}
