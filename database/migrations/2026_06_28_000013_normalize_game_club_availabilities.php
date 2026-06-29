<?php

use App\Enums\GameClubType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_clubs', function (Blueprint $table): void {
            $table->string('team_type', 30)->default(GameClubType::Club->value)->after('name')->index();
            $table->char('country_code', 2)->nullable()->after('team_type')->index();
        });

        Schema::create('game_club_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_club_id')->constrained()->cascadeOnDelete();
            $table->string('game', 40);
            $table->unique(['game_club_id', 'game']);
            $table->index('game');
        });

        $canonicalIds = [];

        foreach (DB::table('game_clubs')->orderBy('id')->get() as $club) {
            $identity = filled($club->external_provider) && filled($club->external_id)
                ? "external:{$club->external_provider}:{$club->external_id}"
                : 'name:'.mb_strtolower(trim($club->name));
            $canonicalId = $canonicalIds[$identity] ?? null;

            if ($canonicalId === null) {
                $canonicalIds[$identity] = $club->id;
                $canonicalId = $club->id;
            } else {
                DB::table('tournament_players')->where('game_club_id', $club->id)->update(['game_club_id' => $canonicalId]);
                DB::table('tournament_teams')->where('game_club_id', $club->id)->update(['game_club_id' => $canonicalId]);
            }

            DB::table('game_club_availabilities')->insertOrIgnore([
                'game_club_id' => $canonicalId,
                'game' => $club->game,
            ]);

            if ($canonicalId !== $club->id) {
                DB::table('game_clubs')->where('id', $club->id)->delete();
            }
        }

        Schema::table('game_clubs', function (Blueprint $table): void {
            $table->dropUnique('game_clubs_game_name_unique');
            $table->dropUnique('game_clubs_game_external_provider_external_id_unique');
            $table->dropIndex('game_clubs_game_index');
            $table->dropColumn('game');
            $table->unique(['team_type', 'name']);
            $table->unique(['external_provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('game_clubs', function (Blueprint $table): void {
            $table->dropUnique(['team_type', 'name']);
            $table->dropUnique(['external_provider', 'external_id']);
            $table->string('game', 40)->default('ea_sports_fc')->index();
        });

        foreach (DB::table('game_clubs')->get() as $club) {
            $game = DB::table('game_club_availabilities')->where('game_club_id', $club->id)->value('game');
            DB::table('game_clubs')->where('id', $club->id)->update(['game' => $game ?? 'ea_sports_fc']);
        }

        Schema::dropIfExists('game_club_availabilities');

        Schema::table('game_clubs', function (Blueprint $table): void {
            $table->dropColumn(['team_type', 'country_code']);
            $table->unique(['game', 'name']);
            $table->unique(['game', 'external_provider', 'external_id']);
        });
    }
};
