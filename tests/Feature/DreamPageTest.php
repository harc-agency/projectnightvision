<?php

use App\Models\Dream;
use App\Models\Symbol;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('dream page resolves linked symbol images through the symbol media route', function () {
    Storage::fake('public');
    Storage::disk('public')->put('symbol-images/flood.png', 'image-bytes');
    config()->set('services.openai.symbol_target_count', 1);

    $user = User::factory()->create();

    $dream = Dream::factory()
        ->for($user)
        ->create([
            'analysis' => 'A flood overtakes the town.',
            'overall_theme' => 'Overwhelm',
            'sentiment' => 'negative',
        ]);

    $symbol = Symbol::create([
        'symbol_key' => 'flood',
        'title' => 'Flood',
        'description' => 'Represents overwhelming emotions or circumstances.',
        'featured_image' => '/storage/symbol-images/flood.png',
    ]);

    $dream->symbols()->attach($symbol->id);

    $response = $this
        ->actingAs($user)
        ->get(route('dreams.show', ['dream' => $dream->id]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dreams/show')
        ->where('dream.symbols.0.featured_image', route('symbols.media', [
            'symbol' => $symbol->symbol_key,
            'kind' => 'image',
        ])));
});
