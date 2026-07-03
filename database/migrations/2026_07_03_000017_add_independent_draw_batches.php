<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_draws', function (Blueprint $table): void {
            $table->index('tournament_id', 'tournament_draws_tournament_id_index');
        });

        Schema::table('tournament_draws', function (Blueprint $table): void {
            $table->dropUnique(['tournament_id']);
            $table->unsignedSmallInteger('batch_number')->default(1)->after('tournament_id');
            $table->string('name')->nullable()->after('batch_number');
            $table->boolean('is_final_stage')->default(false)->after('name');
            $table->unsignedBigInteger('winner_id')->nullable()->after('is_final_stage');
            $table->dateTime('completed_at')->nullable()->after('winner_id');
            $table->unique(['tournament_id', 'batch_number']);
        });

        Schema::table('rounds', function (Blueprint $table): void {
            $table->dropUnique(['tournament_id', 'bracket', 'number']);
            $table->foreignId('tournament_draw_id')->nullable()->after('tournament_id')->constrained('tournament_draws')->cascadeOnDelete();
            $table->unique(['tournament_draw_id', 'bracket', 'number'], 'rounds_draw_bracket_number_unique');
        });

        Schema::table('matches', function (Blueprint $table): void {
            $table->foreignId('tournament_draw_id')->nullable()->after('tournament_id')->constrained('tournament_draws')->cascadeOnDelete();
            $table->index(['tournament_draw_id', 'status']);
        });

        DB::table('tournament_draws')->orderBy('id')->each(function (object $draw): void {
            DB::table('tournament_draws')->where('id', $draw->id)->update(['name' => 'Tanda '.$draw->batch_number]);
            DB::table('rounds')->where('tournament_id', $draw->tournament_id)->whereNull('tournament_draw_id')->update(['tournament_draw_id' => $draw->id]);
        });
        DB::table('rounds')->whereNotNull('tournament_draw_id')->orderBy('id')->each(function (object $round): void {
            DB::table('matches')->where('round_id', $round->id)->whereNull('tournament_draw_id')->update(['tournament_draw_id' => $round->tournament_draw_id]);
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->dropIndex(['tournament_draw_id', 'status']);
            $table->dropConstrainedForeignId('tournament_draw_id');
        });
        Schema::table('rounds', function (Blueprint $table): void {
            $table->dropUnique('rounds_draw_bracket_number_unique');
            $table->dropConstrainedForeignId('tournament_draw_id');
            $table->unique(['tournament_id', 'bracket', 'number']);
        });
        Schema::table('tournament_draws', function (Blueprint $table): void {
            $table->dropUnique(['tournament_id', 'batch_number']);
            $table->dropColumn(['batch_number', 'name', 'is_final_stage', 'winner_id', 'completed_at']);
            $table->unique('tournament_id');
        });
        Schema::table('tournament_draws', function (Blueprint $table): void {
            $table->dropIndex('tournament_draws_tournament_id_index');
        });
    }
};
