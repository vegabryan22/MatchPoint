<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->unsignedInteger('duration_seconds')->nullable()->after('scheduled_at');
            $table->text('observations')->nullable()->after('duration_seconds');
            $table->foreignId('completed_by')->nullable()->after('observations')->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable()->after('completed_by')->index();
        });

        Schema::create('scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('game_number');
            $table->unsignedSmallInteger('participant_a_score');
            $table->unsignedSmallInteger('participant_b_score');
            $table->unsignedBigInteger('winner_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['match_id', 'game_number']);
            $table->index(['winner_id', 'match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');

        Schema::table('matches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn(['duration_seconds', 'observations', 'completed_at']);
        });
    }
};
