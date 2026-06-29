<?php

namespace App\Models;

use App\Enums\GameClubType;
use App\Enums\GameType;
use Database\Factories\GameClubFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameClub extends Model
{
    /** @use HasFactory<GameClubFactory> */
    use HasFactory;

    protected $fillable = ['name', 'team_type', 'country_code', 'crest_path', 'crest_url', 'external_provider', 'external_id', 'is_active'];

    protected function casts(): array
    {
        return ['team_type' => GameClubType::class, 'is_active' => 'boolean'];
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(GameClubAvailability::class);
    }

    public function supportsGame(GameType $game): bool
    {
        if ($this->relationLoaded('availabilities')) {
            return $this->availabilities->contains(fn (GameClubAvailability $availability): bool => $availability->game === $game);
        }

        return $this->availabilities()->where('game', $game->value)->exists();
    }

    public function countryFlag(): ?string
    {
        if ($this->country_code === null) {
            return null;
        }

        return collect(str_split(mb_strtoupper($this->country_code)))
            ->map(fn (string $letter): string => mb_chr(127397 + ord($letter)))
            ->implode('');
    }

    public function crestUrl(): ?string
    {
        if ($this->crest_path !== null) {
            return asset('storage/'.ltrim($this->crest_path, '/'));
        }

        return $this->crest_url;
    }
}
