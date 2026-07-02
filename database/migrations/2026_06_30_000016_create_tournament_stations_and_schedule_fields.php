<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->unsignedSmallInteger('match_duration_minutes')->default(15)->after('best_of');
            $table->unsignedSmallInteger('turnaround_minutes')->default(5)->after('match_duration_minutes');
        });

        Schema::create('tournament_stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('platform', 30);
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->timestamps();
            $table->unique(['tournament_id', 'name']);
        });

        Schema::table('matches', function (Blueprint $table): void {
            $table->foreignId('tournament_station_id')->nullable()->after('scheduled_at')->constrained('tournament_stations')->nullOnDelete();
            $table->dateTime('scheduled_end_at')->nullable()->after('tournament_station_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tournament_station_id');
            $table->dropColumn('scheduled_end_at');
        });

        Schema::dropIfExists('tournament_stations');

        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropColumn(['match_duration_minutes', 'turnaround_minutes']);
        });
    }
};
