<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_clubs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('game', 40)->index();
            $table->string('crest_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['game', 'name']);
        });

        Schema::table('tournament_players', function (Blueprint $table): void {
            $table->foreignId('game_club_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('tournament_teams', function (Blueprint $table): void {
            $table->foreignId('game_club_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tournament_teams', fn (Blueprint $table) => $table->dropConstrainedForeignId('game_club_id'));
        Schema::table('tournament_players', fn (Blueprint $table) => $table->dropConstrainedForeignId('game_club_id'));
        Schema::dropIfExists('game_clubs');
    }
};
