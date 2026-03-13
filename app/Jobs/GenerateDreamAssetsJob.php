<?php

namespace App\Jobs;

use App\Models\Dream;
use App\Services\OpenAiDreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GenerateDreamAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $dreamId,
        public bool $force = false,
    ) {}

    public function handle(OpenAiDreamService $openAiDreamService): void
    {
        $dream = Dream::query()->find($this->dreamId);

        if (!$dream) {
            return;
        }

        $targetSymbolCount = max((int) config('services.openai.symbol_target_count', 3), 1);
        $needsAnalysis = $this->force
            || empty($dream->analysis)
            || empty($dream->overall_theme)
            || empty($dream->sentiment);
        $linkedSymbols = $this->linkedSymbols($dream, $targetSymbolCount);
        $needsSymbols = $this->force || $linkedSymbols->count() < $targetSymbolCount;

        if ($needsAnalysis) {
            try {
                $analysis = $openAiDreamService->analyzeDream($dream->dream_content);

                if (filled(trim((string) $dream->title))) {
                    unset($analysis['title']);
                }

                $dream->update($analysis);
            } catch (\Throwable $e) {
                Log::error('Dream analysis generation failed', [
                    'dream_id' => $dream->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($needsSymbols) {
            AnalyzeSymbolsJob::dispatchSync($dream->fresh());
            $linkedSymbols = $this->linkedSymbols($dream->fresh(), $targetSymbolCount);
        }

        $this->dispatchMissingSymbolImageJobs($linkedSymbols, $dream->id);
    }

    protected function linkedSymbols(Dream $dream, int $targetSymbolCount): Collection
    {
        return $dream->symbols()
            ->orderBy('dream_symbol.id')
            ->limit($targetSymbolCount)
            ->get();
    }

    protected function dispatchMissingSymbolImageJobs(Collection $symbols, int $dreamId): void
    {
        foreach ($symbols as $symbol) {
            if (filled($symbol->featured_image)) {
                continue;
            }

            GenerateSymbolImageJob::dispatch($symbol->id, $dreamId);
        }
    }
}
