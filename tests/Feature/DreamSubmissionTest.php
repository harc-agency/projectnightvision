<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('dream submission requires a title', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('dreams.create'))
        ->post(route('dreams.store'), [
            'title' => '',
            'dream_content' => 'A dream without a valid title should be rejected.',
        ]);

    $response
        ->assertSessionHasErrors('title')
        ->assertRedirect(route('dreams.create'));
});

test('dream submission resolves a typed location into stored coordinates', function () {
    Queue::fake();
    Http::fake([
        'https://photon.komoot.io/*' => Http::response([
            'features' => [
                [
                    'geometry' => [
                        'coordinates' => [-104.990251, 39.739236],
                    ],
                    'properties' => [
                        'name' => 'Denver',
                        'city' => 'Denver',
                        'state' => 'Colorado',
                        'country' => 'United States',
                    ],
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('dreams.store'), [
            'title' => 'Roofline Walk',
            'dream_content' => 'I crossed rooftops under a bright orange sky.',
            'dream_location' => '  Denver, Colorado  ',
            'is_public' => true,
        ]);

    $dream = $user->dreams()->latest('id')->first();

    expect($dream)->not->toBeNull();

    $response->assertRedirect(route('dreams.show', $dream));

    expect($dream->dream_location)->toBe('Denver, Colorado')
        ->and($dream->location)->toMatchArray([
            'lat' => 39.739236,
            'lng' => -104.990251,
            'label' => 'Denver, Colorado, United States',
            'source' => 'location_predictor',
        ])
        ->and($dream->is_public)->toBeTrue();
});

test('dream submission can save a typed location to the user profile', function () {
    Queue::fake();
    Http::fake([
        'https://photon.komoot.io/*' => Http::response([
            'features' => [
                [
                    'geometry' => [
                        'coordinates' => [-105.937799, 35.687],
                    ],
                    'properties' => [
                        'name' => 'Santa Fe',
                        'city' => 'Santa Fe',
                        'state' => 'New Mexico',
                        'country' => 'United States',
                    ],
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'preferred_dream_location' => 'Boulder, Colorado',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('dreams.store'), [
            'title' => 'Desert Arcade',
            'dream_content' => 'A long arcade appeared in the middle of a red canyon.',
            'dream_location' => 'Santa Fe, New Mexico',
            'save_location_to_profile' => true,
            'is_public' => true,
        ]);

    $dream = $user->dreams()->latest('id')->first();

    expect($dream)->not->toBeNull();

    $response->assertRedirect(route('dreams.show', $dream));

    expect($dream->dream_location)->toBe('Santa Fe, New Mexico')
        ->and($user->fresh()->preferred_dream_location)->toBe('Santa Fe, New Mexico');
});

test('dream submission keeps a typed location when predictor cannot resolve it', function () {
    Queue::fake();
    Http::fake([
        'https://photon.komoot.io/*' => Http::response([
            'features' => [],
        ]),
    ]);

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('dreams.store'), [
            'title' => 'Roofline Walk',
            'dream_content' => 'I crossed rooftops under a bright orange sky.',
            'dream_location' => '  Somewhere beyond the station  ',
            'is_public' => true,
        ]);

    $dream = $user->dreams()->latest('id')->first();

    expect($dream)->not->toBeNull();

    $response->assertRedirect(route('dreams.show', $dream));

    expect($dream->dream_location)->toBe('Somewhere beyond the station')
        ->and($dream->location)->toBeNull()
        ->and($dream->is_public)->toBeTrue();
});

test('dream submission stores browser geolocation as a backup location', function () {
    Queue::fake();

    $user = User::factory()->create();
    $capturedAt = now()->toISOString();

    $response = $this
        ->actingAs($user)
        ->post(route('dreams.store'), [
            'title' => 'Harbor Lights',
            'dream_content' => 'I followed a row of blue lights toward the water.',
            'dream_location' => '',
            'location' => [
                'lat' => 47.606209,
                'lng' => -122.332069,
                'accuracy' => 18,
                'source' => 'browser_geolocation',
                'captured_at' => $capturedAt,
            ],
            'is_public' => false,
        ]);

    $dream = $user->dreams()->latest('id')->first();

    expect($dream)->not->toBeNull();

    $response->assertRedirect(route('dreams.show', $dream));

    expect($dream->dream_location)->toBeNull()
        ->and($dream->location)->toMatchArray([
            'lat' => 47.606209,
            'lng' => -122.332069,
            'accuracy' => 18.0,
            'source' => 'browser_geolocation',
            'captured_at' => $capturedAt,
        ])
        ->and($dream->location)->not->toHaveKey('label')
        ->and($dream->is_public)->toBeFalse();
});
