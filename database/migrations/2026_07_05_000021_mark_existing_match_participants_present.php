<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('matches')
            ->where('status', 'completed')
            ->whereNotNull('participant_a_id')
            ->whereNotNull('participant_b_id')
            ->orderBy('id')
            ->chunkById(200, function ($matches): void {
                foreach ($matches as $match) {
                    $table = $match->participant_type === 'individual' ? 'tournament_players' : 'tournament_teams';
                    $participantColumn = $match->participant_type === 'individual' ? 'player_id' : 'team_id';

                    foreach ([$match->participant_a_id, $match->participant_b_id] as $participantId) {
                        DB::table($table)
                            ->where('tournament_id', $match->tournament_id)
                            ->where($participantColumn, $participantId)
                            ->where('attendance_status', '!=', 'present')
                            ->update([
                                'attendance_status' => 'present',
                                'checked_in_at' => $match->completed_at ?? now(),
                                'checked_in_by' => $match->completed_by,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Historical attendance inferred from completed matches must not be discarded.
    }
};
