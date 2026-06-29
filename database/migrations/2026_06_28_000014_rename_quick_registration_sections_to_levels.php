<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->renameColumn('quick_registration_sections', 'quick_registration_levels');
        });
        Schema::table('tournament_players', function (Blueprint $table): void {
            $table->renameColumn('section', 'academic_level');
        });

        DB::table('tournaments')->whereNotNull('quick_registration_levels')->orderBy('id')->each(function (object $tournament): void {
            $levels = collect(json_decode($tournament->quick_registration_levels, true) ?: [])
                ->map(fn (string $value): ?string => $this->normalizeLevel($value))
                ->filter()
                ->unique()
                ->values()
                ->all();

            DB::table('tournaments')->where('id', $tournament->id)->update([
                'quick_registration_levels' => json_encode($levels),
            ]);
        });

        DB::table('tournament_players')->whereNotNull('academic_level')->orderBy('id')->each(function (object $registration): void {
            DB::table('tournament_players')->where('id', $registration->id)->update([
                'academic_level' => $this->normalizeLevel($registration->academic_level),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tournament_players', function (Blueprint $table): void {
            $table->renameColumn('academic_level', 'section');
        });
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->renameColumn('quick_registration_levels', 'quick_registration_sections');
        });
    }

    private function normalizeLevel(string $value): ?string
    {
        preg_match('/^(7|8|9|10|11|12)(?:\D|$)/', trim($value), $matches);

        return $matches[1] ?? null;
    }
};
