<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LocationPredictionService
{
    public function search(string $query, int $limit = 5): array
    {
        $query = $this->normalizeQuery($query);
        $limit = max(1, min($limit, 8));

        if ($query === '' || mb_strlen($query) < 2) {
            return [];
        }

        try {
            $response = Http::acceptJson()
                ->timeout(4)
                ->retry(1, 150)
                ->withHeaders([
                    'User-Agent' => config('app.name', 'Project Night Vision') . ' location predictor',
                ])
                ->get(config('services.location_predictor.endpoint'), [
                    'q' => $query,
                    'limit' => $limit,
                    'lang' => app()->getLocale(),
                ]);
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $features = $response->json('features');

        if (! is_array($features)) {
            return [];
        }

        return collect($features)
            ->map(fn ($feature) => $this->mapFeature($feature, $query))
            ->filter()
            ->unique(fn (array $prediction) => implode('|', [
                $prediction['lat'],
                $prediction['lng'],
                mb_strtolower($prediction['label']),
            ]))
            ->take($limit)
            ->values()
            ->all();
    }

    public function resolve(string $query): ?array
    {
        return $this->search($query, 1)[0] ?? null;
    }

    protected function mapFeature(mixed $feature, string $fallbackLabel): ?array
    {
        if (! is_array($feature)) {
            return null;
        }

        $coordinates = data_get($feature, 'geometry.coordinates');
        $properties = data_get($feature, 'properties');

        if (! is_array($coordinates) || count($coordinates) < 2 || ! is_array($properties)) {
            return null;
        }

        $lng = $coordinates[0] ?? null;
        $lat = $coordinates[1] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = round((float) $lat, 6);
        $lng = round((float) $lng, 6);

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return [
            'label' => $this->buildLabel($properties, $fallbackLabel),
            'lat' => $lat,
            'lng' => $lng,
            'source' => config('services.location_predictor.source', 'location_predictor'),
        ];
    }

    protected function buildLabel(array $properties, string $fallbackLabel): string
    {
        $primary = $properties['name'] ?? null;
        $locality = $properties['city']
            ?? $properties['town']
            ?? $properties['village']
            ?? $properties['county']
            ?? $properties['district']
            ?? $properties['state_district']
            ?? null;
        $region = $properties['state'] ?? $properties['province'] ?? null;
        $country = $properties['country'] ?? null;

        $parts = Collection::make([$primary, $locality, $region, $country])
            ->map(function ($value) {
                if (! is_string($value)) {
                    return null;
                }

                $value = Str::squish($value);

                return $value !== '' ? $value : null;
            })
            ->filter()
            ->unique(fn (string $value) => mb_strtolower($value))
            ->values();

        $label = $parts->implode(', ');

        return Str::limit($label !== '' ? $label : $fallbackLabel, 255);
    }

    protected function normalizeQuery(string $query): string
    {
        return Str::squish($query);
    }
}
