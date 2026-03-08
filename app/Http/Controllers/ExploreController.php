<?php

namespace App\Http\Controllers;

use App\Models\Dream;
use App\Models\Symbol;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ExploreController extends Controller
{
    public function globe()
    {
        $dreams = Dream::query()
            ->where('is_public', true)
            ->latest('created_at')
            ->limit(300)
            ->get(['id', 'title', 'sentiment', 'overall_theme', 'location', 'created_at']);

        $sentiment = [
            'positive' => $dreams->where('sentiment', 'positive')->count(),
            'neutral' => $dreams->where('sentiment', 'neutral')->count(),
            'negative' => $dreams->where('sentiment', 'negative')->count(),
        ];

        $themes = $dreams
            ->filter(fn ($dream) => !empty($dream->overall_theme))
            ->groupBy('overall_theme')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(8)
            ->map(fn ($count, $theme) => ['theme' => $theme, 'count' => $count])
            ->values();

        $points = $dreams
            ->map(function ($dream) {
                $coords = $this->extractCoordinates($dream->location);

                if ($coords === null) {
                    return null;
                }

                return [
                    'id' => $dream->id,
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                    'title' => $dream->title ?: 'Untitled Dream',
                    'sentiment' => $dream->sentiment ?: 'neutral',
                    'theme' => $dream->overall_theme,
                ];
            })
            ->filter()
            ->take(120)
            ->values();

        return Inertia::render('Globe', [
            'stats' => [
                'total_public_dreams' => $dreams->count(),
                'sentiment' => $sentiment,
                'themes' => $themes,
            ],
            'points' => $points,
        ]);
    }

    public function library()
    {
        $dreams = Dream::query()
            ->where('is_public', true)
            ->with([
                'user:id,name',
                'symbols:id,symbol_key,title',
            ])
            ->latest('dream_date')
            ->latest('created_at')
            ->limit(150)
            ->get(['id', 'user_id', 'title', 'dream_content', 'overall_theme', 'sentiment', 'dream_date', 'created_at']);

        $dreamCards = $dreams->map(function (Dream $dream): array {
            return [
                'id' => $dream->id,
                'title' => $dream->title ?: 'Untitled Dream',
                'excerpt' => Str::limit(trim((string) $dream->dream_content), 220),
                'overall_theme' => $dream->overall_theme,
                'sentiment' => $dream->sentiment ?: 'neutral',
                'dream_date' => optional($dream->dream_date)->format('Y-m-d'),
                'author_name' => $dream->user?->name ?: 'Anonymous',
                'symbols' => $dream->symbols
                    ->take(5)
                    ->map(fn (Symbol $symbol) => [
                        'symbol_key' => $symbol->symbol_key,
                        'title' => $symbol->title,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        return Inertia::render('Library', [
            'dreams' => $dreamCards,
        ]);
    }

    protected function extractCoordinates(mixed $location): ?array
    {
        if (!is_array($location)) {
            return null;
        }

        $lat = $location['lat'] ?? $location['latitude'] ?? null;
        $lng = $location['lng'] ?? $location['lon'] ?? $location['longitude'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }
}
