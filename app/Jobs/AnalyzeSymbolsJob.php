<?php

namespace App\Jobs;

use App\Models\Dream;
use App\Models\Symbol;
use App\Services\OpenAiDreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalyzeSymbolsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dream;

    /**
     * Create a new job instance.
     */
    public function __construct(Dream $dream)
    {
        $this->dream = $dream;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAiDreamService $openAiDreamService)
    {
        Log::info('AnalyzeSymbolsJob started for dream', ['dream_id' => $this->dream->id]);

        try {
            $targetSymbolCount = max((int) config('services.openai.symbol_target_count', 3), 1);
            $knownSymbols = Symbol::query()->get();

            $existingSymbols = $knownSymbols->map(function ($symbol) {
                return [
                    'title' => $symbol->title,
                    'description' => $symbol->description,
                ];
            })->toArray();

            Log::info('Fetched existing symbols', ['count' => count($existingSymbols)]);

            $symbolsData = $openAiDreamService->extractSymbols(
                $this->dream->dream_content,
                $existingSymbols,
            );

            if (empty($symbolsData)) {
                Log::warning('No symbols returned from OpenAI');

                return;
            }

            Log::info('Symbols received from OpenAI', ['symbols' => $symbolsData]);

            $linkedSymbolIds = [];
            $processedSymbolIds = [];

            foreach ($symbolsData as $symbolData) {
                // Log each symbol being processed
                Log::info('Processing symbol', ['symbol' => $symbolData]);

                $symbol = $this->resolveSymbol($knownSymbols, $symbolData);

                if (! $symbol || in_array($symbol->id, $processedSymbolIds, true)) {
                    continue;
                }

                $processedSymbolIds[] = $symbol->id;
                $linkedSymbolIds[] = $symbol->id;

                if (count($linkedSymbolIds) >= $targetSymbolCount) {
                    break;
                }
            }

            if ($linkedSymbolIds !== []) {
                $this->dream->symbols()->sync($linkedSymbolIds);
            }

            Log::info('Symbols synced with dream', ['dream_id' => $this->dream->id, 'symbol_ids' => $linkedSymbolIds]);

        } catch (\Exception $e) {
            Log::error('AnalyzeSymbolsJob encountered an error', [
                'dream_id' => $this->dream->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Log::info('AnalyzeSymbolsJob completed for dream', ['dream_id' => $this->dream->id]);
    }

    protected function resolveSymbol(Collection $knownSymbols, array $symbolData): ?Symbol
    {
        $title = trim((string) ($symbolData['title'] ?? ''));
        $description = trim((string) ($symbolData['description'] ?? ''));

        if ($title === '' || $description === '') {
            return null;
        }

        $matchingSymbol = $this->findMatchingSymbol($knownSymbols, $title);

        if ($matchingSymbol) {
            if (blank($matchingSymbol->description) && $description !== '') {
                $matchingSymbol->update(['description' => $description]);
            }

            return $matchingSymbol;
        }

        $symbol = Symbol::create([
            'title' => $title,
            'description' => $description,
            'symbol_key' => $this->buildUniqueSymbolKey($title),
        ]);

        $knownSymbols->push($symbol);

        return $symbol;
    }

    protected function findMatchingSymbol(Collection $knownSymbols, string $title): ?Symbol
    {
        $normalizedTitle = Str::lower(trim($title));
        $candidateFingerprints = $this->titleFingerprints($title);

        return $knownSymbols->first(function (Symbol $symbol) use ($normalizedTitle, $candidateFingerprints) {
            if (Str::lower(trim((string) $symbol->title)) === $normalizedTitle) {
                return true;
            }

            return ! empty(array_intersect(
                $candidateFingerprints,
                $this->titleFingerprints((string) $symbol->title),
            ));
        });
    }

    /**
     * @return array<int, string>
     */
    protected function titleFingerprints(string $title): array
    {
        $fingerprints = [];

        foreach ([$title, ...$this->splitTitleVariants($title)] as $variant) {
            $fingerprint = $this->normalizeTitleFingerprint($variant);

            if ($fingerprint === '') {
                continue;
            }

            $fingerprints[$fingerprint] = $fingerprint;
        }

        return array_values($fingerprints);
    }

    /**
     * @return array<int, string>
     */
    protected function splitTitleVariants(string $title): array
    {
        $variants = [];

        if (preg_match('/^(.*?)\s*\((.*?)\)\s*$/u', $title, $matches)) {
            foreach ([$matches[1], $matches[2]] as $match) {
                $variant = trim((string) $match);

                if ($variant !== '') {
                    $variants[] = $variant;
                }
            }
        }

        $parts = preg_split('/\s*(?:\/|\||;)\s*/u', $title) ?: [];

        foreach ($parts as $part) {
            $variant = trim((string) $part);

            if ($variant !== '' && $variant !== $title) {
                $variants[] = $variant;
            }
        }

        return array_values(array_unique($variants));
    }

    protected function normalizeTitleFingerprint(string $title): string
    {
        $cleaned = preg_replace('/[^\pL\pN]+/u', ' ', Str::lower(trim($title)));

        if (! is_string($cleaned) || trim($cleaned) === '') {
            return '';
        }

        $tokens = preg_split('/\s+/u', trim($cleaned)) ?: [];
        $tokens = array_values(array_filter(array_map(function (string $token) {
            $singular = Str::lower(Str::singular($token));

            if (in_array($singular, ['a', 'an', 'the'], true)) {
                return null;
            }

            return $singular;
        }, $tokens)));

        if ($tokens === []) {
            return '';
        }

        $tokens = array_values(array_unique($tokens));
        sort($tokens);

        return implode(' ', $tokens);
    }

    protected function buildUniqueSymbolKey(string $title): string
    {
        $baseKey = Str::slug($title, '_');
        $baseKey = $baseKey !== '' ? $baseKey : 'symbol';
        $symbolKey = $baseKey;
        $suffix = 2;

        while (Symbol::query()->where('symbol_key', $symbolKey)->exists()) {
            $symbolKey = $baseKey.'_'.$suffix;
            $suffix++;
        }

        return $symbolKey;
    }
}
