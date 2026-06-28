<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('qualifiers_count')->default(0);
            $table->timestamps();
            $table->unique(['tournament_id', 'position']);
        });

        Schema::create('group_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('participant_type', 30);
            $table->unsignedBigInteger('participant_id');
            $table->unsignedSmallInteger('seed')->nullable();
            $table->timestamps();
            $table->unique(['group_id', 'participant_type', 'participant_id'], 'group_participant_unique');
            $table->index(['participant_type', 'participant_id']);
        });

        Schema::table('matches', function (Blueprint $table): void {
            $table->foreignId('group_id')->nullable()->after('round_id')->constrained('groups')->cascadeOnDelete();
            $table->index(['group_id', 'status']);
        });

        Schema::table('scores', function (Blueprint $table): void {
            $table->unsignedBigInteger('winner_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table): void {
            $table->unsignedBigInteger('winner_id')->nullable(false)->change();
        });
        Schema::table('matches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('group_id');
        });
        Schema::dropIfExists('group_participants');
        Schema::dropIfExists('groups');
    }
};
