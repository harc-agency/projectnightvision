<?php

use App\Models\Dream;
use App\Models\Symbol;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('symbol page falls back to matching dreams when pivot links are missing', function () {
    $user = User::factory()->create();

    $dream = Dream::factory()
        ->for($user)
        ->create([
            'title' => 'Apocalypse Warning',
            'dream_content' => 'The apocalypse unfolded over the city skyline.',
            'is_public' => false,
        ]);

    $symbol = Symbol::create([
        'symbol_key' => 'apocalypse',
        'title' => 'Apocalypse',
        'description' => 'Represents disruption, endings, and large-scale change.',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('symbols.show', ['symbol' => $symbol->symbol_key]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Symbols/Show')
        ->where('symbol.dreams_count', 1)
        ->has('symbol.dreams', 1)
        ->where('symbol.dreams.0.id', $dream->id)
        ->where('symbol.dreams.0.title', 'Apocalypse Warning'));
});

test('symbol page only shows linked dreams the current user can view', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $otherUser = User::factory()->create();

    $symbol = Symbol::create([
        'symbol_key' => 'flood',
        'title' => 'Flood',
        'description' => 'Represents overwhelming emotions or circumstances.',
    ]);

    $ownedDream = Dream::factory()
        ->for($viewer)
        ->create([
            'title' => 'My Flood Dream',
            'is_public' => false,
        ]);

    $publicDream = Dream::factory()
        ->for($owner)
        ->create([
            'title' => 'Public Flood Dream',
            'is_public' => true,
        ]);

    $privateDream = Dream::factory()
        ->for($otherUser)
        ->create([
            'title' => 'Private Flood Dream',
            'is_public' => false,
        ]);

    $symbol->dreams()->attach([$ownedDream->id, $publicDream->id, $privateDream->id]);

    $response = $this
        ->actingAs($viewer)
        ->get(route('symbols.show', ['symbol' => $symbol->symbol_key]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Symbols/Show')
        ->where('symbol.dreams_count', 2)
        ->has('symbol.dreams', 2)
        ->where('symbol.dreams', fn ($dreams) => collect($dreams)->pluck('id')->sort()->values()->all() === [
            $ownedDream->id,
            $publicDream->id,
        ]));
});
