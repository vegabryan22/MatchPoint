<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tournament_players', 'tournament_teams'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('attendance_status', 20)->default('pending')->after('registered_at')->index();
                $table->timestamp('checked_in_at')->nullable()->after('attendance_status');
                $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['tournament_players', 'tournament_teams'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('checked_in_by');
                $table->dropColumn(['attendance_status', 'checked_in_at']);
            });
        }
    }
};
