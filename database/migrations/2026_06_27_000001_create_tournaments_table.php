<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('game', 40)->index();
            $table->string('custom_game')->nullable();
            $table->string('participant_type', 30)->index();
            $table->unsignedSmallInteger('max_participants');
            $table->string('format', 40)->index();
            $table->unsignedTinyInteger('best_of');
            $table->string('status', 30)->index();
            $table->dateTime('registration_starts_at')->nullable();
            $table->dateTime('registration_ends_at')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
