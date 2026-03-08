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
            $table->string('ai_image_url')->nullable()->after('analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dreams', function (Blueprint $table) {
            $table->dropColumn('ai_image_url');
        });
    }
};
