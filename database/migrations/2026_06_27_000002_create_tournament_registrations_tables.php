<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('manual');
            $table->unsignedSmallInteger('seed')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['tournament_id', 'player_id']);
            $table->unique(['tournament_id', 'seed']);
        });

        Schema::create('tournament_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('manual');
            $table->unsignedSmallInteger('seed')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['tournament_id', 'team_id']);
            $table->unique(['tournament_id', 'seed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_teams');
        Schema::dropIfExists('tournament_players');
    }
};
