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
        Schema::create('dreams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('dream_content');
            $table->boolean('is_public')->default(true);
            $table->dateTime('dream_date')->nullable();
            $table->string('dream_location')->nullable();
            $table->string('mood_before_sleep')->nullable();
            $table->string('mood_after_waking')->nullable();
            $table->integer('intensity')->unsigned()->nullable();
            $table->integer('sleep_quality')->unsigned()->nullable();
            $table->string('overall_theme')->nullable();
            $table->text('analysis')->nullable();
            $table->string('sentiment')->nullable();
            $table->float('sleep_duration')->nullable();
            $table->json('location')->nullable();
            $table->json('weather')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dreams');
    }
};
