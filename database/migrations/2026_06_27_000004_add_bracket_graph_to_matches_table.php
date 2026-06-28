<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->foreignId('winner_next_match_id')->nullable()->after('winner_id')->constrained('matches')->nullOnDelete();
            $table->string('winner_next_slot', 1)->nullable()->after('winner_next_match_id');
            $table->foreignId('loser_next_match_id')->nullable()->after('winner_next_slot')->constrained('matches')->nullOnDelete();
            $table->string('loser_next_slot', 1)->nullable()->after('loser_next_match_id');
            $table->boolean('is_conditional')->default(false)->after('loser_next_slot');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('loser_next_match_id');
            $table->dropConstrainedForeignId('winner_next_match_id');
            $table->dropColumn(['winner_next_slot', 'loser_next_slot', 'is_conditional']);
        });
    }
};
