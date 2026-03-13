<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('symbols')
            ->whereIn('symbol_key', array_column($this->legacySymbols(), 'symbol_key'))
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $timestamp = now();

        $rows = array_map(
            fn (array $symbol): array => $symbol + [
                'featured_image' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            $this->legacySymbols(),
        );

        DB::table('symbols')->upsert(
            $rows,
            ['symbol_key'],
            ['title', 'description', 'featured_image', 'updated_at'],
        );
    }

    /**
     * @return array<int, array{symbol_key: string, title: string, description: string}>
     */
    private function legacySymbols(): array
    {
        return [
            [
                'symbol_key' => 'snake',
                'title' => 'Snake',
                'description' => 'A common dream symbol often representing transformation or hidden fears.',
            ],
            [
                'symbol_key' => 'cat',
                'title' => 'Cat',
                'description' => 'A symbol of independence and intuition, often connected with feminine energy.',
            ],
            [
                'symbol_key' => 'dog',
                'title' => 'Dog',
                'description' => 'Represents loyalty, friendship, or protection.',
            ],
            [
                'symbol_key' => 'water',
                'title' => 'Water',
                'description' => 'Often tied to emotions, cleansing, or the unconscious mind.',
            ],
            [
                'symbol_key' => 'fire',
                'title' => 'Fire',
                'description' => 'Symbolizes passion, anger, or transformation.',
            ],
            [
                'symbol_key' => 'flying',
                'title' => 'Flying',
                'description' => 'Represents freedom, ambition, or control in dreams.',
            ],
            [
                'symbol_key' => 'falling',
                'title' => 'Falling',
                'description' => 'A common dream that signifies insecurity or loss of control.',
            ],
            [
                'symbol_key' => 'key',
                'title' => 'Key',
                'description' => 'Represents access, solutions, or new opportunities.',
            ],
            [
                'symbol_key' => 'mirror',
                'title' => 'Mirror',
                'description' => 'Symbolizes self-reflection and self-awareness.',
            ],
            [
                'symbol_key' => 'death',
                'title' => 'Death',
                'description' => 'Often signifies transformation or fear of endings.',
            ],
        ];
    }
};
