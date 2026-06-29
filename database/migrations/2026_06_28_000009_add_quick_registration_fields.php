<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->boolean('quick_registration_enabled')->default(false)->index();
            $table->json('quick_registration_sections')->nullable();
            $table->string('quick_registration_notice', 1000)->nullable();
        });

        Schema::table('players', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->string('country', 100)->nullable()->change();
            $table->boolean('is_quick_entry')->default(false)->index();
        });

        Schema::table('tournament_players', function (Blueprint $table): void {
            $table->string('section', 80)->nullable()->index();
            $table->string('controller_platform', 10)->nullable();
            $table->dateTime('controller_acknowledged_at')->nullable();
            $table->string('public_reference', 16)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('tournament_players', function (Blueprint $table): void {
            $table->dropUnique(['public_reference']);
            $table->dropColumn(['section', 'controller_platform', 'controller_acknowledged_at', 'public_reference']);
        });

        Schema::table('players', function (Blueprint $table): void {
            $table->dropColumn('is_quick_entry');
            $table->string('email')->nullable(false)->change();
            $table->string('country', 100)->nullable(false)->change();
        });

        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropColumn(['quick_registration_enabled', 'quick_registration_sections', 'quick_registration_notice']);
        });
    }
};
