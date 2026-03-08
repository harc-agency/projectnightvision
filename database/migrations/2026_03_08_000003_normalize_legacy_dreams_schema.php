<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('dreams')) {
            return;
        }

        Schema::table('dreams', function (Blueprint $table) {
            if (!Schema::hasColumn('dreams', 'dream_content')) {
                $table->text('dream_content')->nullable();
            }

            if (!Schema::hasColumn('dreams', 'is_public')) {
                $table->boolean('is_public')->default(true);
            }

            if (!Schema::hasColumn('dreams', 'dream_date')) {
                $table->dateTime('dream_date')->nullable();
            }

            if (!Schema::hasColumn('dreams', 'overall_theme')) {
                $table->string('overall_theme')->nullable();
            }

            if (!Schema::hasColumn('dreams', 'analysis')) {
                $table->text('analysis')->nullable();
            }

            if (!Schema::hasColumn('dreams', 'sentiment')) {
                $table->string('sentiment')->nullable();
            }

            if (!Schema::hasColumn('dreams', 'ai_image_url')) {
                $table->string('ai_image_url')->nullable();
            }

            if (!Schema::hasColumn('dreams', 'dream_audio_path')) {
                $table->string('dream_audio_path')->nullable();
            }
        });

        $this->copyColumnValues('content', 'dream_content');
        $this->copyColumnValues('public', 'is_public');
        $this->copyColumnValues('theme', 'overall_theme');
        $this->copyColumnValues('date', 'dream_date');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left as a no-op for schema safety across divergent prod environments.
    }

    protected function copyColumnValues(string $sourceColumn, string $targetColumn): void
    {
        if (!Schema::hasColumn('dreams', $sourceColumn) || !Schema::hasColumn('dreams', $targetColumn)) {
            return;
        }

        DB::statement(
            sprintf(
                'UPDATE `dreams` SET `%s` = `%s` WHERE `%s` IS NULL AND `%s` IS NOT NULL',
                $targetColumn,
                $sourceColumn,
                $targetColumn,
                $sourceColumn,
            ),
        );
    }
};
