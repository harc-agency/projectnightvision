<?php

use App\Models\Dream;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('globe page maps only public dreams with valid coordinates', function () {
    $user = User::factory()->create();

    $mappedDream = Dream::factory()
        ->for($user)
        ->create([
            'title' => 'Mapped Dream',
            'is_public' => true,
            'sentiment' => 'positive',
            'overall_theme' => 'Wonder',
            'location' => [
                'lat' => 40.7128,
                'lng' => -74.0060,
                'label' => 'New York, US',
            ],
        ]);

    Dream::factory()
        ->for($user)
        ->create([
            'is_public' => true,
            'location' => null,
        ]);

    Dream::factory()
        ->for($user)
        ->create([
            'is_public' => false,
            'location' => [
                'lat' => 51.5072,
                'lng' => -0.1276,
                'label' => 'London, GB',
            ],
        ]);

    $response = $this->get(route('globe'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Globe')
        ->where('stats.total_public_dreams', 2)
        ->where('stats.sentiment.positive', 1)
        ->has('points', 1)
        ->where('points.0.id', $mappedDream->id)
        ->where('points.0.title', 'Mapped Dream')
        ->where('points.0.lat', 40.7128)
        ->where('points.0.lng', -74.006)
        ->where('points.0.location_label', 'New York, US')
        ->where('points.0.sentiment', 'positive')
        ->where('points.0.theme', 'Wonder'));
});

test('globe page supports legacy double-encoded location payloads', function () {
    $user = User::factory()->create();

    DB::table('dreams')->insert([
        'user_id' => $user->id,
        'title' => 'Legacy Location Dream',
        'dream_content' => 'Legacy encoded location payload.',
        'is_public' => true,
        'dream_date' => now(),
        'sentiment' => 'neutral',
        'overall_theme' => 'Memory',
        'location' => json_encode(json_encode([
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'city' => 'San Francisco',
            'country' => 'US',
        ])),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get(route('globe'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Globe')
        ->has('points', 1)
        ->where('points.0.title', 'Legacy Location Dream')
        ->where('points.0.lat', 37.7749)
        ->where('points.0.lng', -122.4194)
        ->where('points.0.location_label', 'San Francisco, US'));
});
