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
        if (Schema::hasColumn('dreams', 'dream_audio_path')) {
            return;
        }

        Schema::table('dreams', function (Blueprint $table) {
            if (Schema::hasColumn('dreams', 'ai_image_url')) {
                $table->string('dream_audio_path')->nullable()->after('ai_image_url');
                return;
            }

            $table->string('dream_audio_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('dreams', 'dream_audio_path')) {
            return;
        }

        Schema::table('dreams', function (Blueprint $table) {
            $table->dropColumn('dream_audio_path');
        });
    }
};
