<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('notification_preferences', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->boolean('email_enabled')->default(true);
            $t->boolean('database_enabled')->default(true);
            $t->boolean('match_reminders')->default(true);
            $t->boolean('results')->default(true);
            $t->boolean('champions')->default(true);
            $t->timestamps();
        });
        Schema::create('match_reminders', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('window', 10);
            $t->timestamp('sent_at');
            $t->unique(['match_id', 'user_id', 'window']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_reminders');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
