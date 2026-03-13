<?php

use App\Jobs\AnalyzeSymbolsJob;
use App\Models\Dream;
use App\Models\Symbol;
use App\Services\OpenAiDreamService;
use Illuminate\Support\Facades\DB;

test('analyze symbols reuses an existing symbol when the generated title is an alias variant', function () {
    $dream = Dream::factory()->create([
        'dream_content' => 'Sirens blared and I ran from a sudden explosion.',
    ]);

    $existingSymbol = Symbol::create([
        'symbol_key' => 'bombs',
        'title' => 'Bombs',
        'description' => 'Symbolize sudden change, destruction, or emotional upheaval.',
        'featured_image' => 'https://example.com/bombs.png',
    ]);

    $service = new class extends OpenAiDreamService
    {
        public function extractSymbols(string $dreamContent, array $existingSymbols): array
        {
            return [
                [
                    'title' => 'Bombs/Explosions',
                    'description' => 'Symbolize sudden change, destruction, or overwhelming emotions.',
                ],
            ];
        }

        public function generateSymbolImage(string $title, ?string $description = null, ?string $dreamContext = null): ?string
        {
            throw new RuntimeException('generateSymbolImage should not be called for an existing symbol.');
        }
    };

    (new AnalyzeSymbolsJob($dream))->handle($service);

    expect(Symbol::query()->count())->toBe(1)
        ->and($dream->fresh()->symbols)->toHaveCount(1)
        ->and($dream->fresh()->symbols->first()->is($existingSymbol))->toBeTrue();
});

test('analyze symbols deduplicates near-identical titles returned in the same batch', function () {
    $dream = Dream::factory()->create([
        'dream_content' => 'Explosions echoed through the city while I looked for cover.',
    ]);

    $service = new class extends OpenAiDreamService
    {
        public int $generateSymbolImageCalls = 0;

        public function extractSymbols(string $dreamContent, array $existingSymbols): array
        {
            return [
                [
                    'title' => 'Bombs/Explosions',
                    'description' => 'Symbolize sudden change, destruction, or overwhelming emotions.',
                ],
                [
                    'title' => 'Bombs',
                    'description' => 'Symbolize sudden change, destruction, or emotional upheaval.',
                ],
            ];
        }

        public function generateSymbolImage(string $title, ?string $description = null, ?string $dreamContext = null): ?string
        {
            $this->generateSymbolImageCalls++;

            return null;
        }
    };

    (new AnalyzeSymbolsJob($dream))->handle($service);

    expect(Symbol::query()->count())->toBe(1)
        ->and($dream->fresh()->symbols)->toHaveCount(1)
        ->and($service->generateSymbolImageCalls)->toBe(0);
});

test('analyze symbols links a symbol to the dream before image generation starts', function () {
    $dream = Dream::factory()->create([
        'dream_content' => 'A flood rushed through the city while I searched for higher ground.',
    ]);

    $service = new class extends OpenAiDreamService
    {
        public function extractSymbols(string $dreamContent, array $existingSymbols): array
        {
            return [
                [
                    'title' => 'Flood',
                    'description' => 'Represents overwhelming emotions or circumstances.',
                ],
            ];
        }
    };

    (new AnalyzeSymbolsJob($dream))->handle($service);

    expect($dream->fresh()->symbols)->toHaveCount(1)
        ->and(DB::table('dream_symbol')
            ->where('dream_id', $dream->id)
            ->exists())->toBeTrue();
});

test('analyze symbols keeps only the top target number of symbols for a dream', function () {
    $dream = Dream::factory()->create([
        'dream_content' => 'Apocalypse, flood, infection, and horses all appeared in the same dream.',
    ]);

    $service = new class extends OpenAiDreamService
    {
        public function extractSymbols(string $dreamContent, array $existingSymbols): array
        {
            return [
                [
                    'title' => 'Apocalypse',
                    'description' => 'Represents large-scale disruption and uncertainty.',
                ],
                [
                    'title' => 'Flood',
                    'description' => 'Represents overwhelming emotions.',
                ],
                [
                    'title' => 'Infection',
                    'description' => 'Represents contamination or spreading problems.',
                ],
                [
                    'title' => 'Horse',
                    'description' => 'Represents freedom and momentum.',
                ],
            ];
        }

        public function generateSymbolImage(string $title, ?string $description = null, ?string $dreamContext = null): ?string
        {
            return null;
        }
    };

    (new AnalyzeSymbolsJob($dream))->handle($service);

    expect($dream->fresh()->symbols)->toHaveCount(3)
        ->and(Symbol::query()->where('title', 'Horse')->exists())->toBeFalse();
});
