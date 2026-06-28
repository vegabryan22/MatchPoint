<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_champions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('participant_type', 30);
            $table->unsignedBigInteger('participant_id');
            $table->foreignId('deciding_match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->timestamp('crowned_at');
            $table->timestamps();
            $table->index(['participant_type', 'participant_id']);
            $table->index('crowned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_champions');
    }
};
