<?php

namespace App\Http\Controllers;

use App\Models\Dream;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $dreams = Dream::query()
            ->where('user_id', auth()->id())
            ->with(['symbols:id,symbol_key,title'])
            ->latest('dream_date')
            ->latest('created_at')
            ->get([
                'id',
                'user_id',
                'title',
                'analysis',
                'overall_theme',
                'sentiment',
                'dream_date',
                'created_at',
                'updated_at',
                'location',
                'dream_location',
                'is_public',
            ]);

        return Inertia::render('Dashboard', [
            'insights' => $this->buildInsights(
                $dreams,
                max((int) config('services.openai.symbol_target_count', 3), 1),
            ),
        ]);
    }

    protected function buildInsights(Collection $dreams, int $targetSymbolCount): array
    {
        $dreams = $dreams
            ->map(fn (Dream $dream) => [
                'dream' => $dream,
                'effective_at' => $this->effectiveAt($dream),
            ])
            ->sortByDesc(fn (array $item) => $item['effective_at']->timestamp)
            ->values();

        $now = CarbonImmutable::now();
        $recentStart = $now->startOfDay()->subDays(29);
        $previousStart = $now->startOfDay()->subDays(59);
        $previousEnd = $recentStart->subDay()->endOfDay();

        $recentDreams = $dreams->filter(
            fn (array $item) => $item['effective_at']->gte($recentStart),
        )->values();

        $previousDreams = $dreams->filter(
            fn (array $item) => $item['effective_at']->betweenIncluded($previousStart, $previousEnd),
        )->values();

        $summary = $this->buildSummary($dreams, $recentDreams, $targetSymbolCount);
        $sentiment = $this->buildSentimentInsights($dreams, $recentDreams, $previousDreams, $now);
        $themes = $this->buildThemeInsights($dreams, $recentDreams, $previousDreams);
        $symbols = $this->buildSymbolInsights($dreams, $recentDreams, $previousDreams);
        $locations = $this->buildLocationInsights($dreams);
        $analysisStatus = $this->buildAnalysisStatus($dreams, $targetSymbolCount);
        $related = $this->buildRelatedInsights($dreams);

        return [
            'has_dreams' => $dreams->isNotEmpty(),
            'summary' => $summary,
            'headline' => $this->buildHeadline($summary, $themes, $symbols, $sentiment),
            'analysis_status' => $analysisStatus,
            'sentiment' => $sentiment,
            'themes' => $themes,
            'symbols' => $symbols,
            'locations' => $locations,
            'related' => $related,
        ];
    }

    protected function buildSummary(Collection $dreams, Collection $recentDreams, int $targetSymbolCount): array
    {
        $totalDreams = $dreams->count();
        $analyzedDreams = $dreams->filter(
            fn (array $item) => $this->isAnalyzed($item['dream']),
        )->count();
        $pendingAnalysis = $totalDreams - $analyzedDreams;
        $distinctDates = $dreams
            ->pluck('effective_at')
            ->map(fn (CarbonImmutable $date) => $date->toDateString())
            ->unique()
            ->values();

        $lastEntry = $dreams->first()['effective_at'] ?? null;

        return [
            'total_dreams' => $totalDreams,
            'analyzed_dreams' => $analyzedDreams,
            'pending_analysis' => $pendingAnalysis,
            'entries_last_7_days' => $dreams->filter(
                fn (array $item) => $item['effective_at']->gte(CarbonImmutable::now()->startOfDay()->subDays(6)),
            )->count(),
            'entries_last_30_days' => $recentDreams->count(),
            'current_streak_days' => $this->calculateCurrentStreak($distinctDates),
            'average_gap_days' => $this->calculateAverageGapDays($distinctDates),
            'most_common_weekday' => $this->mostCommonWeekday($dreams),
            'last_entry_at' => $lastEntry?->toDateString(),
            'public_dreams' => $dreams->filter(
                fn (array $item) => (bool) $item['dream']->is_public,
            )->count(),
            'dreams_with_symbols' => $dreams->filter(
                fn (array $item) => $item['dream']->symbols->count() >= $targetSymbolCount,
            )->count(),
        ];
    }

    protected function buildAnalysisStatus(Collection $dreams, int $targetSymbolCount): array
    {
        $totalDreams = $dreams->count();
        $analyzedDreams = $dreams->filter(
            fn (array $item) => $this->isAnalyzed($item['dream']),
        );
        $withSymbols = $dreams->filter(
            fn (array $item) => $item['dream']->symbols->count() >= $targetSymbolCount,
        );
        $latestAnalyzed = $analyzedDreams->sortByDesc(
            fn (array $item) => $item['effective_at']->timestamp,
        )->first();

        return [
            'analyzed_dreams' => $analyzedDreams->count(),
            'pending_analysis' => max($totalDreams - $analyzedDreams->count(), 0),
            'analysis_coverage_ratio' => $totalDreams > 0
                ? round($analyzedDreams->count() / $totalDreams, 2)
                : 0,
            'dreams_with_symbols' => $withSymbols->count(),
            'pending_symbol_links' => max($totalDreams - $withSymbols->count(), 0),
            'symbol_coverage_ratio' => $totalDreams > 0
                ? round($withSymbols->count() / $totalDreams, 2)
                : 0,
            'latest_analyzed_entry_at' => $latestAnalyzed !== null
                ? $latestAnalyzed['effective_at']->toDateString()
                : null,
            'latest_analyzed_title' => $latestAnalyzed !== null
                ? ($latestAnalyzed['dream']->title ?: 'Untitled Dream')
                : null,
        ];
    }

    protected function buildHeadline(
        array $summary,
        array $themes,
        array $symbols,
        array $sentiment,
    ): array {
        if (($summary['total_dreams'] ?? 0) === 0) {
            return [
                'primary_theme' => null,
                'top_symbols' => [],
                'sentiment_direction_30d' => 'steady',
                'message' => 'Start logging dreams to reveal recurring themes, symbols, and mood shifts.',
            ];
        }

        $primaryTheme = $themes[0]['name'] ?? null;
        $topSymbols = collect($symbols)
            ->take(2)
            ->pluck('title')
            ->values()
            ->all();
        $direction = $sentiment['direction_30d'] ?? 'steady';

        $parts = [];

        if ($primaryTheme) {
            $parts[] = $primaryTheme . ' is your strongest recurring theme';
        }

        if (count($topSymbols) === 1) {
            $parts[] = $topSymbols[0] . ' is showing up repeatedly';
        } elseif (count($topSymbols) >= 2) {
            $parts[] = implode(' and ', array_slice($topSymbols, 0, 2)) . ' are recurring symbols';
        }

        if ($direction === 'up') {
            $parts[] = 'recent sentiment is trending more positive';
        } elseif ($direction === 'down') {
            $parts[] = 'recent sentiment is trending more negative';
        } else {
            $parts[] = 'recent sentiment is holding steady';
        }

        return [
            'primary_theme' => $primaryTheme,
            'top_symbols' => $topSymbols,
            'sentiment_direction_30d' => $direction,
            'message' => ucfirst(implode('; ', $parts)) . '.',
        ];
    }

    protected function buildSentimentInsights(
        Collection $dreams,
        Collection $recentDreams,
        Collection $previousDreams,
        CarbonImmutable $now,
    ): array {
        $distribution = [
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0,
        ];

        foreach ($recentDreams as $item) {
            $sentiment = $this->normalizeSentiment($item['dream']->sentiment);

            if ($sentiment !== null) {
                $distribution[$sentiment]++;
            }
        }

        $trend = collect(range(7, 0))
            ->map(function (int $offset) use ($dreams, $now): array {
                $start = $now->startOfWeek()->subWeeks($offset);
                $end = $start->endOfWeek();
                $weeklyDreams = $dreams->filter(
                    fn (array $item) => $item['effective_at']->betweenIncluded($start, $end),
                );

                $positive = 0;
                $neutral = 0;
                $negative = 0;

                foreach ($weeklyDreams as $item) {
                    $sentiment = $this->normalizeSentiment($item['dream']->sentiment);

                    if ($sentiment === 'positive') {
                        $positive++;
                    } elseif ($sentiment === 'negative') {
                        $negative++;
                    } elseif ($sentiment === 'neutral') {
                        $neutral++;
                    }
                }

                return [
                    'period_start' => $start->toDateString(),
                    'label' => $start->format('M j'),
                    'positive' => $positive,
                    'neutral' => $neutral,
                    'negative' => $negative,
                    'total' => $positive + $neutral + $negative,
                ];
            })
            ->values()
            ->all();

        $recentScore = $this->sentimentScore($recentDreams);
        $previousScore = $this->sentimentScore($previousDreams);
        $delta = $recentScore - $previousScore;

        return [
            'distribution_30d' => $distribution,
            'trend' => $trend,
            'classified_count_30d' => array_sum($distribution),
            'recent_score' => round($recentScore, 2),
            'previous_score' => round($previousScore, 2),
            'direction_30d' => $delta > 0.1 ? 'up' : ($delta < -0.1 ? 'down' : 'steady'),
        ];
    }

    protected function buildThemeInsights(
        Collection $dreams,
        Collection $recentDreams,
        Collection $previousDreams,
    ): array {
        $totals = $this->groupCounts(
            $dreams,
            fn (Dream $dream) => $dream->overall_theme,
        );
        $recentTotals = $this->groupCounts(
            $recentDreams,
            fn (Dream $dream) => $dream->overall_theme,
        );
        $previousTotals = $this->groupCounts(
            $previousDreams,
            fn (Dream $dream) => $dream->overall_theme,
        );

        $analyzedDreams = $dreams->filter(
            fn (array $item) => $this->isAnalyzed($item['dream']),
        )->count();

        return $totals
            ->map(function (array $theme) use ($recentTotals, $previousTotals, $analyzedDreams): array {
                $recentCount = $recentTotals->get($theme['key'])['count'] ?? 0;
                $previousCount = $previousTotals->get($theme['key'])['count'] ?? 0;

                return [
                    'name' => $theme['label'],
                    'count' => $theme['count'],
                    'share' => $analyzedDreams > 0 ? round($theme['count'] / $analyzedDreams, 2) : 0,
                    'delta_vs_prev_30d' => $recentCount - $previousCount,
                    'last_seen_at' => $theme['last_seen_at'],
                ];
            })
            ->sort(function (array $left, array $right): int {
                return ($right['count'] <=> $left['count'])
                    ?: strcmp($left['name'], $right['name']);
            })
            ->take(5)
            ->values()
            ->all();
    }

    protected function buildSymbolInsights(
        Collection $dreams,
        Collection $recentDreams,
        Collection $previousDreams,
    ): array {
        $totals = $this->groupCounts(
            $dreams,
            function (Dream $dream): array {
                return $dream->symbols
                    ->map(fn ($symbol) => [
                        'key' => $this->normalizeKey($symbol->symbol_key ?: $symbol->title),
                        'label' => trim((string) ($symbol->title ?: $symbol->symbol_key)),
                    ])
                    ->filter(fn (array $symbol) => $symbol['key'] !== null && $symbol['label'] !== '')
                    ->values()
                    ->all();
            },
            true,
        );
        $recentTotals = $this->groupCounts(
            $recentDreams,
            function (Dream $dream): array {
                return $dream->symbols
                    ->map(fn ($symbol) => [
                        'key' => $this->normalizeKey($symbol->symbol_key ?: $symbol->title),
                        'label' => trim((string) ($symbol->title ?: $symbol->symbol_key)),
                    ])
                    ->filter(fn (array $symbol) => $symbol['key'] !== null && $symbol['label'] !== '')
                    ->values()
                    ->all();
            },
            true,
        );
        $previousTotals = $this->groupCounts(
            $previousDreams,
            function (Dream $dream): array {
                return $dream->symbols
                    ->map(fn ($symbol) => [
                        'key' => $this->normalizeKey($symbol->symbol_key ?: $symbol->title),
                        'label' => trim((string) ($symbol->title ?: $symbol->symbol_key)),
                    ])
                    ->filter(fn (array $symbol) => $symbol['key'] !== null && $symbol['label'] !== '')
                    ->values()
                    ->all();
            },
            true,
        );

        $totalDreams = max($dreams->count(), 1);

        return $totals
            ->map(function (array $symbol) use ($recentTotals, $previousTotals, $totalDreams): array {
                $recentCount = $recentTotals->get($symbol['key'])['count'] ?? 0;
                $previousCount = $previousTotals->get($symbol['key'])['count'] ?? 0;
                $delta = $recentCount - $previousCount;

                return [
                    'symbol_key' => $symbol['key'],
                    'title' => $symbol['label'],
                    'count' => $symbol['count'],
                    'share' => round($symbol['count'] / $totalDreams, 2),
                    'last_seen_at' => $symbol['last_seen_at'],
                    'trend' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'steady'),
                    'is_new' => $recentCount > 0 && $previousCount === 0,
                ];
            })
            ->sort(function (array $left, array $right): int {
                return ($right['count'] <=> $left['count'])
                    ?: strcmp($left['title'], $right['title']);
            })
            ->take(6)
            ->values()
            ->all();
    }

    protected function buildLocationInsights(Collection $dreams): array
    {
        $locations = [];

        foreach ($dreams as $item) {
            $label = $this->extractLocationLabel($item['dream']);

            if ($label === null) {
                continue;
            }

            $key = $this->normalizeKey($label);

            if ($key === null) {
                continue;
            }

            if (!isset($locations[$key])) {
                $locations[$key] = [
                    'label' => $label,
                    'count' => 0,
                    'last_seen_at' => $item['effective_at']->toDateString(),
                ];
            }

            $locations[$key]['count']++;

            if ($item['effective_at']->toDateString() > $locations[$key]['last_seen_at']) {
                $locations[$key]['last_seen_at'] = $item['effective_at']->toDateString();
            }
        }

        $taggedCount = count($locations) > 0
            ? array_sum(array_column($locations, 'count'))
            : 0;

        $topLocations = collect($locations)
            ->sort(function (array $left, array $right): int {
                return ($right['count'] <=> $left['count'])
                    ?: strcmp($left['label'], $right['label']);
            })
            ->take(3)
            ->values()
            ->all();

        return [
            'tagged_count' => $taggedCount,
            'tagged_rate' => $dreams->count() > 0
                ? round($taggedCount / $dreams->count(), 2)
                : 0,
            'top' => $topLocations,
            'show_card' => $taggedCount >= 3,
        ];
    }

    protected function buildRelatedInsights(Collection $dreams): array
    {
        $anchor = $dreams->first();

        if ($anchor === null) {
            return [
                'basis_title' => null,
                'revisit' => [],
            ];
        }

        /** @var Dream $anchorDream */
        $anchorDream = $anchor['dream'];
        $anchorTheme = $this->normalizeKey($anchorDream->overall_theme);
        $anchorSymbols = $anchorDream->symbols
            ->map(function ($symbol): ?array {
                $key = $this->normalizeKey((string) ($symbol->symbol_key ?: $symbol->title));
                $title = trim((string) $symbol->title);

                if ($key === null || $title === '') {
                    return null;
                }

                return [
                    'key' => $key,
                    'title' => $title,
                ];
            })
            ->filter()
            ->mapWithKeys(fn (array $symbol) => [$symbol['key'] => $symbol['title']])
            ->all();

        $revisit = $dreams
            ->slice(1)
            ->map(function (array $item) use ($anchorTheme, $anchorSymbols): array {
                /** @var Dream $candidate */
                $candidate = $item['dream'];

                $sharedSymbols = $candidate->symbols
                    ->filter(function ($symbol) use ($anchorSymbols) {
                        $key = $this->normalizeKey((string) ($symbol->symbol_key ?: $symbol->title));

                        return $key !== null && array_key_exists($key, $anchorSymbols);
                    })
                    ->pluck('title')
                    ->unique()
                    ->values()
                    ->take(3)
                    ->all();
                $themeMatch = $anchorTheme !== null
                    && $this->normalizeKey($candidate->overall_theme) === $anchorTheme;

                $summaryParts = [];

                if ($themeMatch) {
                    $summaryParts[] = 'Shared theme';
                }

                if (!empty($sharedSymbols)) {
                    $summaryParts[] = 'Shared symbols: ' . implode(', ', $sharedSymbols);
                }

                if (empty($summaryParts)) {
                    $summaryParts[] = 'Recent entry';
                }

                return [
                    'id' => $candidate->id,
                    'title' => $candidate->title ?: 'Untitled Dream',
                    'dream_date' => $item['effective_at']->toDateString(),
                    'sentiment' => $this->normalizeSentiment($candidate->sentiment) ?? 'neutral',
                    'match_summary' => implode(' • ', $summaryParts),
                    'score' => ($themeMatch ? 2 : 0) + count($sharedSymbols),
                ];
            })
            ->sortByDesc('score')
            ->take(3)
            ->map(function (array $item): array {
                unset($item['score']);

                return $item;
            })
            ->values()
            ->all();

        return [
            'basis_title' => $anchorDream->title ?: 'Latest dream',
            'revisit' => $revisit,
        ];
    }

    protected function groupCounts(
        Collection $items,
        callable $valueExtractor,
        bool $multiValue = false,
    ): Collection {
        $totals = [];

        foreach ($items as $item) {
            /** @var Dream $dream */
            $dream = $item['dream'];
            $values = $multiValue
                ? collect($valueExtractor($dream))
                : collect([$valueExtractor($dream)]);

            foreach ($values as $value) {
                if (is_array($value)) {
                    $key = $value['key'] ?? null;
                    $label = trim((string) ($value['label'] ?? ''));
                } else {
                    $label = trim((string) $value);
                    $key = $this->normalizeKey($label);
                }

                if ($key === null || $label === '') {
                    continue;
                }

                if (!isset($totals[$key])) {
                    $totals[$key] = [
                        'key' => $key,
                        'label' => $label,
                        'count' => 0,
                        'last_seen_at' => $item['effective_at']->toDateString(),
                    ];
                }

                $totals[$key]['count']++;

                if ($item['effective_at']->toDateString() > $totals[$key]['last_seen_at']) {
                    $totals[$key]['last_seen_at'] = $item['effective_at']->toDateString();
                }
            }
        }

        return collect($totals);
    }

    protected function effectiveAt(Dream $dream): CarbonImmutable
    {
        return CarbonImmutable::instance($dream->dream_date ?? $dream->created_at ?? now());
    }

    protected function isAnalyzed(Dream $dream): bool
    {
        return filled(trim((string) $dream->analysis))
            && filled(trim((string) $dream->overall_theme))
            && $this->normalizeSentiment($dream->sentiment) !== null;
    }

    protected function normalizeSentiment(?string $sentiment): ?string
    {
        $normalized = strtolower(trim((string) $sentiment));

        return in_array($normalized, ['positive', 'neutral', 'negative'], true)
            ? $normalized
            : null;
    }

    protected function sentimentScore(Collection $dreams): float
    {
        $scores = $dreams
            ->map(function (array $item): ?int {
                return match ($this->normalizeSentiment($item['dream']->sentiment)) {
                    'positive' => 1,
                    'negative' => -1,
                    'neutral' => 0,
                    default => null,
                };
            })
            ->filter(fn ($value) => $value !== null)
            ->values();

        if ($scores->isEmpty()) {
            return 0;
        }

        return (float) $scores->avg();
    }

    protected function calculateCurrentStreak(Collection $distinctDates): int
    {
        if ($distinctDates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = CarbonImmutable::parse($distinctDates->first());

        foreach ($distinctDates as $dateString) {
            $date = CarbonImmutable::parse($dateString);

            if (!$date->isSameDay($cursor)) {
                break;
            }

            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    protected function calculateAverageGapDays(Collection $distinctDates): float
    {
        if ($distinctDates->count() < 2) {
            return 0;
        }

        $sorted = $distinctDates
            ->map(fn (string $date) => CarbonImmutable::parse($date))
            ->sortBy(fn (CarbonImmutable $date) => $date->timestamp)
            ->values();

        $gaps = collect();

        for ($index = 1; $index < $sorted->count(); $index++) {
            $gaps->push($sorted[$index - 1]->diffInDays($sorted[$index]));
        }

        return round((float) $gaps->avg(), 1);
    }

    protected function mostCommonWeekday(Collection $dreams): ?string
    {
        $weekday = $dreams
            ->map(fn (array $item) => $item['effective_at']->format('l'))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        return is_string($weekday) ? $weekday : null;
    }

    protected function extractLocationLabel(Dream $dream): ?string
    {
        $manualLocation = trim((string) $dream->dream_location);

        if ($manualLocation !== '') {
            return $manualLocation;
        }

        $payload = $this->normalizeLocationPayload($dream->location);

        if ($payload === null) {
            return null;
        }

        $label = trim((string) ($payload['label'] ?? $payload['name'] ?? ''));

        if ($label !== '') {
            return $label;
        }

        $city = trim((string) ($payload['city'] ?? ''));
        $country = trim((string) ($payload['country'] ?? ''));
        $parts = array_filter([$city, $country]);

        if (!empty($parts)) {
            return implode(', ', $parts);
        }

        $lat = $payload['lat'] ?? $payload['latitude'] ?? null;
        $lng = $payload['lng'] ?? $payload['lon'] ?? $payload['longitude'] ?? null;

        if (is_numeric($lat) && is_numeric($lng)) {
            return number_format((float) $lat, 2) . ', ' . number_format((float) $lng, 2);
        }

        return null;
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

        if (is_array($decoded)) {
            return $decoded;
        }

        if (is_string($decoded)) {
            $decodedAgain = json_decode($decoded, true);

            return is_array($decodedAgain) ? $decodedAgain : null;
        }

        return null;
    }

    protected function normalizeKey(?string $value): ?string
    {
        $normalized = trim(mb_strtolower((string) $value));

        return $normalized !== '' ? $normalized : null;
    }
}
