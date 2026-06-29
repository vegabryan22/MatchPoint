<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', fn (Blueprint $table) => $table->foreignId('managed_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete());
        Schema::table('teams', fn (Blueprint $table) => $table->foreignId('managed_by')->nullable()->after('id')->constrained('users')->nullOnDelete());

        Schema::create('tournament_organizers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->dateTime('assigned_at');
            $table->timestamps();
            $table->unique(['tournament_id', 'user_id']);
        });

        Schema::create('tournament_officials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 30)->default('referee');
            $table->boolean('is_active')->default(true);
            $table->dateTime('assigned_at');
            $table->timestamps();
            $table->unique(['tournament_id', 'user_id', 'role']);
        });

        $organizerRoleId = DB::table('roles')->where('slug', 'organizer')->value('id');
        if ($organizerRoleId !== null) {
            DB::table('tournaments')
                ->join('role_user', function ($join) use ($organizerRoleId): void {
                    $join->on('role_user.user_id', '=', 'tournaments.created_by')
                        ->where('role_user.role_id', '=', $organizerRoleId);
                })
                ->select(['tournaments.id', 'tournaments.created_by'])
                ->orderBy('tournaments.id')
                ->each(function (object $tournament): void {
                    DB::table('tournament_organizers')->insert([
                        'tournament_id' => $tournament->id,
                        'user_id' => $tournament->created_by,
                        'assigned_by' => $tournament->created_by,
                        'is_primary' => true,
                        'assigned_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_officials');
        Schema::dropIfExists('tournament_organizers');
        Schema::table('teams', fn (Blueprint $table) => $table->dropConstrainedForeignId('managed_by'));
        Schema::table('players', fn (Blueprint $table) => $table->dropConstrainedForeignId('managed_by'));
    }
};
