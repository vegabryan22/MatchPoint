<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_clubs', function (Blueprint $table): void {
            $table->dropUnique(['external_provider', 'external_id']);
            $table->unique(['game', 'external_provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('game_clubs', function (Blueprint $table): void {
            $table->dropUnique(['game', 'external_provider', 'external_id']);
            $table->unique(['external_provider', 'external_id']);
        });
    }
};
