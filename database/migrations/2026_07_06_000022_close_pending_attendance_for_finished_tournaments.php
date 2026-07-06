<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tournaments')
            ->where('status', 'finished')
            ->orderBy('id')
            ->chunkById(100, function ($tournaments): void {
                foreach ($tournaments as $tournament) {
                    $closedAt = $tournament->ends_at ?? $tournament->updated_at ?? now();

                    foreach (['tournament_players', 'tournament_teams'] as $table) {
                        DB::table($table)
                            ->where('tournament_id', $tournament->id)
                            ->where('attendance_status', 'pending')
                            ->update([
                                'attendance_status' => 'absent',
                                'checked_in_at' => $closedAt,
                                'checked_in_by' => null,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    public function down(): void {}
};
