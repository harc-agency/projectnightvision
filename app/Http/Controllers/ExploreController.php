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
            ->with(['symbols:id,symbol_key,title'])
            ->latest('created_at')
            ->limit(300)
            ->get(['id', 'title', 'sentiment', 'overall_theme', 'location', 'created_at']);

        $sentiment = [
            'positive' => $dreams->where('sentiment', 'positive')->count(),
            'neutral' => $dreams->where('sentiment', 'neutral')->count(),
            'negative' => $dreams->where('sentiment', 'negative')->count(),
        ];

        $symbols = Symbol::query()
            ->whereHas('dreams', fn ($query) => $query->where('is_public', true))
            ->withCount([
                'dreams as public_dreams_count' => fn ($query) => $query->where('is_public', true),
            ])
            ->orderByDesc('public_dreams_count')
            ->orderBy('title')
            ->limit(8)
            ->get(['symbol_key', 'title'])
            ->map(fn (Symbol $symbol) => [
                'symbol_key' => $symbol->symbol_key,
                'title' => $symbol->title,
                'count' => (int) $symbol->public_dreams_count,
            ])
            ->values();

        $points = $dreams
            ->map(function ($dream) {
                $location = $this->extractLocation($dream->location);

                if ($location === null) {
                    return null;
                }

                return [
                    'id' => $dream->id,
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'title' => $dream->title ?: 'Untitled Dream',
                    'sentiment' => $dream->sentiment ?: 'neutral',
                    'theme' => $dream->overall_theme,
                    'location_label' => $location['label'],
                    'symbols' => $dream->symbols
                        ->map(fn (Symbol $symbol) => [
                            'symbol_key' => $symbol->symbol_key,
                            'title' => $symbol->title,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->filter()
            ->take(120)
            ->values();

        return Inertia::render('Globe', [
            'stats' => [
                'total_public_dreams' => $dreams->count(),
                'sentiment' => $sentiment,
                'symbols' => $symbols,
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

    protected function extractLocation(mixed $location): ?array
    {
        $payload = $this->normalizeLocationPayload($location);

        if ($payload === null) {
            return null;
        }

        $lat = $payload['lat'] ?? $payload['latitude'] ?? null;
        $lng = $payload['lng'] ?? $payload['lon'] ?? $payload['longitude'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        $label = $payload['label'] ?? $payload['name'] ?? null;

        if (!is_string($label) || trim($label) === '') {
            $city = $payload['city'] ?? null;
            $country = $payload['country'] ?? null;
            $parts = array_filter([
                is_string($city) ? trim($city) : null,
                is_string($country) ? trim($country) : null,
            ]);
            $label = !empty($parts) ? implode(', ', $parts) : null;
        }

        return [
            'lat' => $lat,
            'lng' => $lng,
            'label' => is_string($label) && trim($label) !== '' ? trim($label) : null,
        ];
    }

    protected function normalizeLocationPayload(mixed $location): ?array
    {
        if (is_array($location)) {
            return $location;
        }

        if (!is_string($location) || trim($location) === '') {
            return null;
        }

        $decoded = json_decode($location, true);

        return is_array($decoded) ? $decoded : null;
    }
}
