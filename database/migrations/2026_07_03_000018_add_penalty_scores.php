<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('scores', 'participant_a_penalties')) {
            Schema::table('scores', function (Blueprint $table): void {
                $table->unsignedSmallInteger('participant_a_penalties')->nullable()->after('participant_b_score');
                $table->unsignedSmallInteger('participant_b_penalties')->nullable()->after('participant_a_penalties');
            });
        }
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table): void {
            $table->dropColumn(['participant_a_penalties', 'participant_b_penalties']);
        });
    }
};
