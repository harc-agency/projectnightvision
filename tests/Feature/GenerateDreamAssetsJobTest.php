<?php

use App\Jobs\GenerateDreamAssetsJob;
use App\Jobs\GenerateSymbolImageJob;
use App\Models\Dream;
use App\Models\Symbol;
use App\Services\OpenAiDreamService;
use Illuminate\Support\Facades\Bus;

test('forced asset regeneration does not rename an existing dream', function () {
    $dream = Dream::factory()->create([
        'title' => 'Keep This Title',
        'analysis' => 'Old analysis',
        'overall_theme' => 'Old theme',
        'sentiment' => 'neutral',
    ]);

    $service = new class extends OpenAiDreamService
    {
        public function analyzeDream(string $dreamContent): array
        {
            return [
                'title' => 'New AI Title',
                'overall_theme' => 'Transformation',
                'analysis' => 'A refreshed interpretation of the dream.',
                'sentiment' => 'positive',
            ];
        }

        public function extractSymbols(string $dreamContent, array $existingSymbols): array
        {
            return [];
        }
    };

    app()->instance(OpenAiDreamService::class, $service);

    (new GenerateDreamAssetsJob($dream->id, true))->handle($service);

    $refreshedDream = $dream->fresh();

    expect($refreshedDream->title)->toBe('Keep This Title')
        ->and($refreshedDream->overall_theme)->toBe('Transformation')
        ->and($refreshedDream->analysis)->toBe('A refreshed interpretation of the dream.')
        ->and($refreshedDream->sentiment)->toBe('positive');
});

test('asset generation retries missing images for already linked target symbols', function () {
    $dream = Dream::factory()->create([
        'analysis' => 'Existing analysis',
        'overall_theme' => 'Disruption',
        'sentiment' => 'neutral',
    ]);

    $symbols = collect([
        Symbol::create([
            'symbol_key' => 'apocalypse',
            'title' => 'Apocalypse',
            'description' => 'Represents disruption.',
            'featured_image' => 'https://example.com/apocalypse.png',
        ]),
        Symbol::create([
            'symbol_key' => 'flood',
            'title' => 'Flood',
            'description' => 'Represents overwhelm.',
            'featured_image' => 'https://example.com/flood.png',
        ]),
        Symbol::create([
            'symbol_key' => 'infection',
            'title' => 'Infection',
            'description' => 'Represents contamination.',
            'featured_image' => null,
        ]),
    ]);

    $dream->symbols()->attach($symbols->pluck('id')->all());
    config()->set('services.openai.symbol_image_queue', 'images');
    Bus::fake();

    $service = new class extends OpenAiDreamService
    {
        public function analyzeDream(string $dreamContent): array
        {
            throw new RuntimeException('analyzeDream should not be called when analysis already exists.');
        }

        public function extractSymbols(string $dreamContent, array $existingSymbols): array
        {
            throw new RuntimeException('extractSymbols should not be called when target symbols already exist.');
        }
    };

    (new GenerateDreamAssetsJob($dream->id))->handle($service);

    Bus::assertDispatched(GenerateSymbolImageJob::class, function (GenerateSymbolImageJob $job) use ($dream, $symbols) {
        return $job->dreamId === $dream->id
            && $job->symbolId === $symbols->last()->id
            && $job->queue === 'images';
    });
});

test('asset generation dispatches a separate image job for each missing symbol', function () {
    $dream = Dream::factory()->create([
        'analysis' => 'Existing analysis',
        'overall_theme' => 'Disruption',
        'sentiment' => 'neutral',
    ]);

    $mountains = Symbol::create([
        'symbol_key' => 'mountains',
        'title' => 'Mountains',
        'description' => 'Represents looming obstacles.',
        'featured_image' => null,
    ]);

    $chapel = Symbol::create([
        'symbol_key' => 'chapel',
        'title' => 'Chapel',
        'description' => 'Represents reflection and ritual.',
        'featured_image' => null,
    ]);

    $demons = Symbol::create([
        'symbol_key' => 'demons',
        'title' => 'Demons',
        'description' => 'Represents fear and threat.',
        'featured_image' => 'https://example.com/demons.png',
    ]);

    $dream->symbols()->attach([
        $demons->id,
        $mountains->id,
        $chapel->id,
    ]);
    config()->set('services.openai.symbol_image_queue', 'images');
    Bus::fake();

    $service = new class extends OpenAiDreamService
    {
        public function analyzeDream(string $dreamContent): array
        {
            throw new RuntimeException('analyzeDream should not be called when analysis already exists.');
        }

        public function extractSymbols(string $dreamContent, array $existingSymbols): array
        {
            throw new RuntimeException('extractSymbols should not be called when target symbols already exist.');
        }
    };

    (new GenerateDreamAssetsJob($dream->id))->handle($service);

    Bus::assertDispatchedTimes(GenerateSymbolImageJob::class, 2);
    Bus::assertDispatched(GenerateSymbolImageJob::class, fn (GenerateSymbolImageJob $job) => $job->symbolId === $mountains->id && $job->queue === 'images');
    Bus::assertDispatched(GenerateSymbolImageJob::class, fn (GenerateSymbolImageJob $job) => $job->symbolId === $chapel->id && $job->queue === 'images');
});

test('generate symbol image job stores the returned image on the symbol', function () {
    $dream = Dream::factory()->create([
        'dream_content' => 'A chapel stood on a mountain beneath a dark sky.',
    ]);

    $symbol = Symbol::create([
        'symbol_key' => 'chapel',
        'title' => 'Chapel',
        'description' => 'Represents reflection and ritual.',
        'featured_image' => null,
    ]);

    $service = new class extends OpenAiDreamService
    {
        public function generateSymbolImage(string $title, ?string $description = null, ?string $dreamContext = null): ?string
        {
            return 'https://example.com/chapel.png';
        }
    };

    (new GenerateSymbolImageJob($symbol->id, $dream->id))->handle($service);

    expect($symbol->fresh()->featured_image)->toBe('https://example.com/chapel.png');
});
