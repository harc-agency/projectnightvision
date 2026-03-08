<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDreamRequest;
use App\Http\Requests\UpdateDreamRequest;
use App\Jobs\GenerateDreamAssetsJob;
use App\Models\Dream;
use App\Services\OpenAiDreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DreamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dreams = auth()->user()
            ->dreams()
            ->latest('created_at') // change to dream_date once that is figured out
            ->get()
            ->toArray();

        return Inertia::render('Dreams/index', ['dreams' => $dreams]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Dreams/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDreamRequest $request)
    {
        $data = $request->validated();
        $audioFile = $request->file('dream_audio');
        unset($data['dream_audio']);
        $data['dream_date'] = now()->format('Y-m-d H:i:s');
        $title = Str::squish((string) ($data['title'] ?? ''));
        $data['title'] = $title !== ''
            ? $title
            : Str::limit(Str::squish((string) $data['dream_content']), 80, '...');
        $data['is_public'] = $request->boolean('is_public');

        if ($audioFile) {
            $data['dream_audio_path'] = $audioFile->store('dream-audio', 'public');
        }

        $dream = auth()->user()->dreams()->create($data);
        GenerateDreamAssetsJob::dispatchAfterResponse($dream->id);

        return redirect()->route('dreams.show', $dream);
    }

    public function transcribe(Request $request, OpenAiDreamService $openAiDreamService)
    {
        $validated = $request->validate([
            'audio' => ['required', 'file', 'mimes:mp3,wav,ogg,m4a,webm,mp4', 'max:25600'],
        ]);

        try {
            $transcript = $openAiDreamService->transcribeAudio($validated['audio']);

            return response()->json([
                'transcript' => $transcript,
            ]);
        } catch (\Throwable $e) {
            Log::error('Dream transcription failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Transcription failed. Please try again.',
            ], 422);
        }
    }

    public function updateVisibility(Request $request, Dream $dream)
    {
        if ($dream->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'is_public' => ['required', 'boolean'],
        ]);

        $dream->update([
            'is_public' => (bool) $validated['is_public'],
        ]);

        return back();
    }

    public function generateAssets(Dream $dream)
    {
        if ($dream->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        GenerateDreamAssetsJob::dispatchAfterResponse($dream->id, true);

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Dream $dream)
    {
        if (!$this->canViewDream($dream)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $dream->load('symbols');
        $dream->setAttribute('ai_image_url', $this->resolveDreamImageUrl($dream));
        $dream->setAttribute('dream_audio_url', $this->resolveDreamAudioUrl($dream));

        return Inertia::render('Dreams/show', [
            'dream' => $dream,
            'related' => $this->buildRelatedDreams($dream),
        ]);
    }

    public function media(Dream $dream, string $kind)
    {
        if (!$this->canViewDream($dream)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($kind === 'image') {
            $storagePath = $this->extractStoragePath($dream->ai_image_url);

            if ($storagePath && Storage::disk('public')->exists($storagePath)) {
                return response()->file(Storage::disk('public')->path($storagePath), [
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }

            if (is_string($dream->ai_image_url) && filter_var($dream->ai_image_url, FILTER_VALIDATE_URL)) {
                return redirect()->away($dream->ai_image_url);
            }

            abort(Response::HTTP_NOT_FOUND);
        }

        if ($kind === 'audio') {
            if (!$dream->dream_audio_path || !Storage::disk('public')->exists($dream->dream_audio_path)) {
                abort(Response::HTTP_NOT_FOUND);
            }

            return response()->file(Storage::disk('public')->path($dream->dream_audio_path), [
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]);
        }

        abort(Response::HTTP_NOT_FOUND);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dream $dream)
    {
        // Add your logic here if needed
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDreamRequest $request, Dream $dream)
    {
        // Add your logic here if needed
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dream $dream)
    {
        // Add your logic here if needed
    }

    protected function canViewDream(Dream $dream): bool
    {
        return $dream->user_id === auth()->id() || $dream->is_public;
    }

    protected function resolveDreamImageUrl(Dream $dream): ?string
    {
        if (!$dream->ai_image_url) {
            return null;
        }

        $storagePath = $this->extractStoragePath($dream->ai_image_url);

        if ($storagePath && Storage::disk('public')->exists($storagePath)) {
            return route('dreams.media', [
                'dream' => $dream->id,
                'kind' => 'image',
            ]);
        }

        if (is_string($dream->ai_image_url) && filter_var($dream->ai_image_url, FILTER_VALIDATE_URL)) {
            return route('dreams.media', [
                'dream' => $dream->id,
                'kind' => 'image',
            ]);
        }

        return null;
    }

    protected function resolveDreamAudioUrl(Dream $dream): ?string
    {
        if (!$dream->dream_audio_path || !Storage::disk('public')->exists($dream->dream_audio_path)) {
            return null;
        }

        return route('dreams.media', [
            'dream' => $dream->id,
            'kind' => 'audio',
        ]);
    }

    protected function extractStoragePath(?string $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);
        $path = is_string($path) ? $path : $value;

        if (!Str::startsWith($path, '/storage/')) {
            return null;
        }

        return ltrim(Str::after($path, '/storage/'), '/');
    }

    protected function buildRelatedDreams(Dream $dream): array
    {
        $theme = trim((string) $dream->overall_theme);
        $symbolIds = $dream->symbols->pluck('id')->all();
        $symbolLookup = $dream->symbols
            ->pluck('title')
            ->filter()
            ->map(fn ($title) => mb_strtolower((string) $title))
            ->all();
        $hasCriteria = $theme !== '' || !empty($symbolIds);
        $ownerId = auth()->id();

        $mapRelatedDream = function (Dream $candidate) use ($symbolLookup, $theme): array {
            $sharedSymbols = $candidate->symbols
                ->pluck('title')
                ->filter(fn ($title) => in_array(mb_strtolower((string) $title), $symbolLookup, true))
                ->values()
                ->take(3)
                ->all();
            $themeMatch = $theme !== '' && strcasecmp((string) $candidate->overall_theme, $theme) === 0;

            $matchSummaryParts = [];
            if ($themeMatch) {
                $matchSummaryParts[] = 'Shared theme';
            }
            if (!empty($sharedSymbols)) {
                $matchSummaryParts[] = 'Shared symbols: ' . implode(', ', $sharedSymbols);
            }
            if (empty($matchSummaryParts) && $candidate->overall_theme) {
                $matchSummaryParts[] = 'Theme: ' . $candidate->overall_theme;
            }

            return [
                'id' => $candidate->id,
                'title' => $candidate->title,
                'dream_date' => $candidate->dream_date?->format('Y-m-d'),
                'sentiment' => $candidate->sentiment,
                'match_summary' => implode(' • ', $matchSummaryParts) ?: 'Related by recency',
            ];
        };

        $buildQuery = function () use ($dream) {
            return Dream::query()
                ->select(['dreams.id', 'dreams.user_id', 'dreams.title', 'dreams.dream_date', 'dreams.sentiment', 'dreams.overall_theme', 'dreams.created_at'])
                ->where('dreams.id', '!=', $dream->id)
                ->with(['symbols:id,title,symbol_key']);
        };

        $applyCriteria = function ($query) use ($theme, $symbolIds) {
            return $query->where(function ($builder) use ($theme, $symbolIds) {
                $hasThemeCondition = false;

                if ($theme !== '') {
                    $builder->where('dreams.overall_theme', $theme);
                    $hasThemeCondition = true;
                }

                if (!empty($symbolIds)) {
                    $method = $hasThemeCondition ? 'orWhereHas' : 'whereHas';
                    $builder->{$method}('symbols', function ($symbolQuery) use ($symbolIds) {
                        $symbolQuery->whereIn('symbols.id', $symbolIds);
                    });
                }
            });
        };

        $ownQuery = $buildQuery()->where('dreams.user_id', $ownerId);
        $publicQuery = $buildQuery()
            ->where('dreams.user_id', '!=', $ownerId)
            ->where('dreams.is_public', true);

        if ($hasCriteria) {
            $applyCriteria($ownQuery);
            $applyCriteria($publicQuery);
        }

        $ownDreams = $ownQuery->latest('dreams.created_at')->limit(4)->get();
        $publicDreams = $publicQuery->latest('dreams.created_at')->limit(4)->get();

        if ($hasCriteria && $ownDreams->isEmpty()) {
            $ownDreams = $buildQuery()
                ->where('dreams.user_id', $ownerId)
                ->latest('dreams.created_at')
                ->limit(4)
                ->get();
        }

        if ($hasCriteria && $publicDreams->isEmpty()) {
            $publicDreams = $buildQuery()
                ->where('dreams.user_id', '!=', $ownerId)
                ->where('dreams.is_public', true)
                ->latest('dreams.created_at')
                ->limit(4)
                ->get();
        }

        return [
            'own' => $ownDreams->map($mapRelatedDream)->values()->all(),
            'public' => $publicDreams->map($mapRelatedDream)->values()->all(),
        ];
    }
}
