<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tournaments', 'extraordinary_registration_enabled')) {
            Schema::table('tournaments', function (Blueprint $table): void {
                $table->boolean('extraordinary_registration_enabled')->default(false)->after('quick_registration_enabled');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropColumn('extraordinary_registration_enabled');
        });
    }
};
