<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_draws', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 30);
            $table->boolean('avoid_rematches')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->json('metadata')->nullable();
            $table->dateTime('generated_at');
            $table->timestamps();
        });

        Schema::create('rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('number');
            $table->string('bracket', 30)->default('main');
            $table->dateTime('starts_at')->nullable();
            $table->timestamps();
            $table->unique(['tournament_id', 'bracket', 'number']);
        });

        Schema::create('matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->nullable()->constrained('rounds')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('participant_type', 30);
            $table->unsignedBigInteger('participant_a_id')->nullable();
            $table->unsignedBigInteger('participant_b_id')->nullable();
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedTinyInteger('best_of');
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['round_id', 'sequence']);
            $table->index(['participant_type', 'participant_a_id', 'participant_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('tournament_draws');
    }
};
