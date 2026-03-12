<?php

namespace App\Jobs;

use App\Models\Dream;
use App\Services\OpenAiDreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        $needsAnalysis = $this->force
            || empty($dream->analysis)
            || empty($dream->overall_theme)
            || empty($dream->sentiment);
        $needsSymbols = $this->force || !$dream->symbols()->exists();

        if ($needsAnalysis) {
            try {
                $analysis = $openAiDreamService->analyzeDream($dream->dream_content);
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
        }
    }
}
