<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_clubs', function (Blueprint $table): void {
            $table->string('crest_url', 1000)->nullable();
            $table->string('external_provider', 40)->nullable()->index();
            $table->string('external_id', 80)->nullable();
            $table->unique(['external_provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('game_clubs', function (Blueprint $table): void {
            $table->dropUnique(['external_provider', 'external_id']);
            $table->dropColumn(['crest_url', 'external_provider', 'external_id']);
        });
    }
};
