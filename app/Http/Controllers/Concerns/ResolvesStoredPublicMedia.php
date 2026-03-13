<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

trait ResolvesStoredPublicMedia
{
    protected function resolveStoredPublicMediaUrl(
        ?string $value,
        string $routeName,
        array $routeParameters = [],
    ): ?string {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $storagePath = $this->extractStoragePath($value);

        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
            return route($routeName, $routeParameters);
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return null;
    }

    protected function respondWithStoredPublicMedia(?string $value)
    {
        $storagePath = $this->extractStoragePath($value);

        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
            return response()->file(Storage::disk('public')->path($storagePath), [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return redirect()->away($value);
        }

        abort(Response::HTTP_NOT_FOUND);
    }

    protected function extractStoragePath(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);
        $path = is_string($path) ? $path : $value;

        if (! Str::startsWith($path, '/storage/')) {
            return null;
        }

        return ltrim(Str::after($path, '/storage/'), '/');
    }
}
