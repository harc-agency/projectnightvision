<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'preferred_dream_location')) {
            return;
        }

        $hasGoogleIdColumn = Schema::hasColumn('users', 'google_id');

        Schema::table('users', function (Blueprint $table) use ($hasGoogleIdColumn) {
            $column = $table->string('preferred_dream_location')->nullable();

            if ($hasGoogleIdColumn) {
                $column->after('google_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'preferred_dream_location')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_dream_location');
        });
    }
};
