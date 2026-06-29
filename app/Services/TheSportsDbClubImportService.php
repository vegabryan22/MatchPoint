<?php

namespace App\Services;

use App\Enums\GameClubType;
use App\Repositories\Contracts\GameClubRepositoryInterface;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class TheSportsDbClubImportService
{
    private const CLUBS = [
        'Real Madrid', 'Barcelona', 'Manchester City', 'Manchester United', 'Liverpool', 'Arsenal',
        'Chelsea', 'Tottenham', 'Bayern Munich', 'Borussia Dortmund', 'Bayer Leverkusen', 'Paris SG',
        'Inter Milan', 'AC Milan', 'Juventus', 'Napoli', 'Atletico Madrid', 'Sevilla',
        'Ajax', 'PSV Eindhoven', 'Benfica', 'FC Porto', 'Sporting Lisbon', 'River Plate',
    ];

    private const NATIONAL_TEAMS = [
        'Costa Rica' => 'CR', 'Argentina' => 'AR', 'Brazil' => 'BR', 'Uruguay' => 'UY',
        'Colombia' => 'CO', 'Ecuador' => 'EC', 'Mexico' => 'MX', 'United States' => 'US',
        'Canada' => 'CA', 'Spain' => 'ES', 'France' => 'FR', 'Germany' => 'DE',
        'England' => 'GB', 'Portugal' => 'PT', 'Netherlands' => 'NL', 'Belgium' => 'BE',
        'Croatia' => 'HR', 'Morocco' => 'MA', 'Senegal' => 'SN', 'Nigeria' => 'NG',
        'Japan' => 'JP', 'South Korea' => 'KR', 'Australia' => 'AU', 'Saudi Arabia' => 'SA',
    ];

    public function __construct(private readonly GameClubRepositoryInterface $clubs) {}

    public function importPopular(array $games, array $catalogs): array
    {
        $definitions = $this->definitions($catalogs);
        $baseUrl = rtrim(config('matchpoint.sports_db.base_url'), '/');
        $apiKey = config('matchpoint.sports_db.api_key');
        $responses = Http::pool(fn (Pool $pool): array => collect($definitions)->mapWithKeys(
            fn (array $definition, string $key): array => [
                $key => $pool->as($key)->acceptJson()->withOptions(['verify' => ! app()->isLocal()])->timeout(12)->get("{$baseUrl}/{$apiKey}/searchteams.php", ['t' => $definition['name']]),
            ],
        )->all());
        $result = ['imported' => 0, 'failed' => 0];

        foreach ($definitions as $key => $definition) {
            $response = $responses[$key] ?? null;
            $teams = $response instanceof Response && $response->successful() ? ($response->json('teams') ?? []) : [];
            $team = collect($teams)->first(fn (array $candidate): bool => ($candidate['strSport'] ?? null) === 'Soccer'
                && mb_strtolower($candidate['strTeam'] ?? '') === mb_strtolower($definition['name']))
                ?? collect($teams)->first(fn (array $candidate): bool => ($candidate['strSport'] ?? null) === 'Soccer');

            if ($team === null || blank($team['idTeam'] ?? null) || blank($team['strBadge'] ?? null)) {
                $result['failed']++;

                continue;
            }

            $this->clubs->updateOrCreate(
                ['external_provider' => 'thesportsdb', 'external_id' => (string) $team['idTeam']],
                [
                    'name' => $definition['name'],
                    'team_type' => $definition['team_type'],
                    'country_code' => $definition['country_code'],
                    'crest_url' => $team['strBadge'],
                    'is_active' => true,
                ],
                $games,
            );
            $result['imported']++;
        }

        return $result;
    }

    private function definitions(array $catalogs): array
    {
        $definitions = [];

        if (in_array('clubs', $catalogs, true)) {
            foreach (self::CLUBS as $index => $name) {
                $definitions['club-'.$index] = [
                    'name' => $name,
                    'team_type' => GameClubType::Club->value,
                    'country_code' => null,
                ];
            }
        }

        if (in_array('national_teams', $catalogs, true)) {
            foreach (self::NATIONAL_TEAMS as $name => $countryCode) {
                $definitions['national-'.str($name)->slug()] = [
                    'name' => $name,
                    'team_type' => GameClubType::NationalTeam->value,
                    'country_code' => $countryCode,
                ];
            }
        }

        return $definitions;
    }
}
