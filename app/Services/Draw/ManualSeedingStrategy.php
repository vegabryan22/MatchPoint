<?php

namespace App\Services\Draw;

use App\Contracts\SeedingStrategyInterface;
use App\Enums\DrawMethod;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class ManualSeedingStrategy implements SeedingStrategyInterface
{
    public function method(): DrawMethod
    {
        return DrawMethod::Manual;
    }

    public function order(Tournament $tournament, Collection $participants, array $data): array
    {
        $seeds = collect($data['seeds'] ?? [])->mapWithKeys(
            fn ($seed, $participantId): array => [(int) $participantId => (int) $seed],
        );
        $participantIds = $participants->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values();
        $seededIds = $seeds->keys()->sort()->values();
        $expectedSeeds = range(1, $participants->count());
        $actualSeeds = $seeds->values()->sort()->values()->all();

        if ($participantIds->all() !== $seededIds->all() || $actualSeeds !== $expectedSeeds) {
            throw ValidationException::withMessages([
                'seeds' => 'Debes asignar una semilla única y consecutiva a cada participante.',
            ]);
        }

        return $seeds->sort()->keys()->map(fn ($id): int => (int) $id)->values()->all();
    }
}
