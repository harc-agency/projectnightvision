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
        Schema::table('dreams', function (Blueprint $table) {
            $table->string('dream_audio_path')->nullable()->after('ai_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dreams', function (Blueprint $table) {
            $table->dropColumn('dream_audio_path');
        });
    }
};
