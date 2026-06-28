<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Repositories\Contracts\TournamentDrawRepositoryInterface;
use App\Services\Brackets\BracketGeneratorResolver;

final class BracketGenerationService
{
    public function __construct(
        private readonly BracketGeneratorResolver $generators,
        private readonly TournamentDrawRepositoryInterface $draws,
        private readonly MatchAdvancementService $advancement,
    ) {}

    public function generate(Tournament $tournament, array $plan): void
    {
        $blueprint = $this->generators->resolve($tournament->format)->build($tournament, $plan);
        $rounds = [];
        $matches = [];

        foreach ($blueprint->rounds() as $key => $round) {
            $rounds[$key] = $this->draws->createRound([
                'tournament_id' => $tournament->id,
                'name' => $round['name'],
                'number' => $round['number'],
                'bracket' => $round['bracket'],
                'starts_at' => $tournament->starts_at,
            ]);
        }

        foreach ($blueprint->matches() as $key => $definition) {
            $matches[$key] = $this->draws->createMatch([
                'tournament_id' => $tournament->id,
                'round_id' => $rounds[$definition['round_key']]->id,
                'sequence' => $definition['sequence'],
                'participant_type' => $tournament->participant_type,
                'participant_a_id' => null,
                'participant_b_id' => null,
                'winner_id' => null,
                'status' => MatchStatus::Pending,
                'best_of' => $tournament->best_of,
                'scheduled_at' => null,
                'is_conditional' => false,
                ...$definition['attributes'],
            ]);
        }

        foreach ($blueprint->matches() as $key => $definition) {
            $links = [];
            if ($definition['winner_target'] !== null) {
                $links['winner_next_match_id'] = $matches[$definition['winner_target']['match']]->id;
                $links['winner_next_slot'] = $definition['winner_target']['slot'];
            }
            if ($definition['loser_target'] !== null) {
                $links['loser_next_match_id'] = $matches[$definition['loser_target']['match']]->id;
                $links['loser_next_slot'] = $definition['loser_target']['slot'];
            }
            if ($links !== []) {
                $matches[$key] = $this->draws->updateMatch($matches[$key], $links);
            }
        }

        collect($matches)
            ->filter(fn (GameMatch $match): bool => $match->status === MatchStatus::Bye)
            ->each(fn (GameMatch $match) => $this->advancement->advance($match));
    }
}
