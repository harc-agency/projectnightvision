<?php

use App\Models\Dream;
use App\Models\Symbol;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard surfaces personalized insights for the authenticated user', function () {
    CarbonImmutable::setTestNow('2026-03-12 10:00:00');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $water = Symbol::query()->create([
        'symbol_key' => 'water',
        'title' => 'Water',
        'description' => 'Flow and emotion.',
    ]);

    $stairway = Symbol::query()->create([
        'symbol_key' => 'stairway',
        'title' => 'Stairway',
        'description' => 'Ascending pressure.',
    ]);

    $bridge = Symbol::query()->create([
        'symbol_key' => 'bridge',
        'title' => 'Bridge',
        'description' => 'Crossing between states.',
    ]);

    $shadow = Symbol::query()->create([
        'symbol_key' => 'shadow',
        'title' => 'Shadow',
        'description' => 'Unknown edges.',
    ]);

    $latestDream = Dream::factory()
        ->for($user)
        ->create([
            'title' => 'Glass River',
            'dream_date' => '2026-03-12 06:00:00',
            'overall_theme' => 'Transformation',
            'analysis' => 'A dream about fluid identity and momentum.',
            'sentiment' => 'positive',
            'dream_location' => 'Denver, Colorado',
            'is_public' => true,
        ]);
    $latestDream->symbols()->attach([$water->id, $stairway->id, $bridge->id]);

    $echoDream = Dream::factory()
        ->for($user)
        ->create([
            'title' => 'Echo Hallway',
            'dream_date' => '2026-03-11 06:00:00',
            'overall_theme' => 'Transformation',
            'analysis' => 'A looping corridor and familiar voices.',
            'sentiment' => 'neutral',
            'dream_location' => 'Denver, Colorado',
            'is_public' => false,
        ]);
    $echoDream->symbols()->attach([$water->id, $stairway->id, $bridge->id]);

    Dream::factory()
        ->for($user)
        ->create([
            'title' => 'Unprocessed Signal',
            'dream_date' => '2026-03-10 06:00:00',
            'overall_theme' => null,
            'analysis' => null,
            'sentiment' => null,
            'dream_location' => null,
            'location' => null,
            'is_public' => false,
        ]);

    $bridgeDream = Dream::factory()
        ->for($user)
        ->create([
            'title' => 'Bridge Chase',
            'dream_date' => '2026-03-09 06:00:00',
            'overall_theme' => 'Escape',
            'analysis' => 'A chase through a suspended span.',
            'sentiment' => 'negative',
            'dream_location' => null,
            'location' => null,
            'is_public' => false,
        ]);
    $bridgeDream->symbols()->attach([$water->id, $bridge->id, $shadow->id]);

    $archiveDream = Dream::factory()
        ->for($user)
        ->create([
            'title' => 'Fog Archive',
            'dream_date' => '2026-02-10 06:00:00',
            'overall_theme' => 'Memory',
            'analysis' => 'Shelves of forgotten names.',
            'sentiment' => 'negative',
            'dream_location' => 'London, England',
            'is_public' => true,
        ]);
    $archiveDream->symbols()->attach([$water->id, $stairway->id, $shadow->id]);

    $stairwellDream = Dream::factory()
        ->for($user)
        ->create([
            'title' => 'Stairwell Return',
            'dream_date' => '2026-03-08 06:00:00',
            'overall_theme' => 'Transformation',
            'analysis' => 'Climbing until the same landing reappears.',
            'sentiment' => 'positive',
            'dream_location' => 'Denver, Colorado',
            'is_public' => false,
        ]);
    $stairwellDream->symbols()->attach([$water->id, $stairway->id, $shadow->id]);

    $otherUsersDream = Dream::factory()
        ->for($otherUser)
        ->create([
            'title' => 'Ignored Dream',
            'dream_date' => '2026-03-12 07:00:00',
            'overall_theme' => 'Noise',
            'analysis' => 'Should not appear on another user dashboard.',
            'sentiment' => 'positive',
            'dream_location' => 'Paris, France',
            'is_public' => true,
        ]);
    $otherUsersDream->symbols()->attach([$water->id, $stairway->id, $bridge->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('insights.has_dreams', true)
        ->where('insights.summary.total_dreams', 6)
        ->where('insights.summary.analyzed_dreams', 5)
        ->where('insights.summary.pending_analysis', 1)
        ->where('insights.summary.entries_last_7_days', 5)
        ->where('insights.summary.entries_last_30_days', 5)
        ->where('insights.summary.current_streak_days', 5)
        ->where('insights.summary.most_common_weekday', 'Tuesday')
        ->where('insights.headline.primary_theme', 'Transformation')
        ->where('insights.headline.top_symbols.0', 'Water')
        ->where('insights.headline.top_symbols.1', 'Stairway')
        ->where('insights.headline.sentiment_direction_30d', 'up')
        ->where('insights.analysis_status.analyzed_dreams', 5)
        ->where('insights.analysis_status.pending_analysis', 1)
        ->where('insights.analysis_status.dreams_with_symbols', 5)
        ->where('insights.analysis_status.latest_analyzed_title', 'Glass River')
        ->where('insights.sentiment.distribution_30d.positive', 2)
        ->where('insights.sentiment.distribution_30d.neutral', 1)
        ->where('insights.sentiment.distribution_30d.negative', 1)
        ->where('insights.themes.0.name', 'Transformation')
        ->where('insights.themes.0.count', 3)
        ->where('insights.symbols.0.title', 'Water')
        ->where('insights.symbols.0.count', 5)
        ->where('insights.locations.tagged_count', 4)
        ->where('insights.locations.top.0.label', 'Denver, Colorado')
        ->where('insights.related.basis_title', 'Glass River')
        ->where('insights.related.revisit.0.title', 'Echo Hallway'));

    CarbonImmutable::setTestNow();
});

test('dashboard returns an empty insight state for a new user', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('insights.has_dreams', false)
        ->where('insights.summary.total_dreams', 0)
        ->where('insights.headline.message', 'Start logging dreams to reveal recurring themes, symbols, and mood shifts.'));
});
