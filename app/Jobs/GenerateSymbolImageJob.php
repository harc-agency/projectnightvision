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
use Illuminate\Support\Facades\Log;

class GenerateSymbolImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    public int $backoff;

    public bool $failOnTimeout = true;

    public function __construct(
        public int $symbolId,
        public int $dreamId,
    ) {
        $this->tries = max((int) config('services.openai.symbol_image_tries', 2), 1);
        $this->timeout = max((int) config('services.openai.symbol_image_timeout', 240), 1);
        $this->backoff = max((int) config('services.openai.symbol_image_backoff', 15), 0);
        $this->onQueue((string) config('services.openai.symbol_image_queue', 'images'));
    }

    public function handle(OpenAiDreamService $openAiDreamService): void
    {
        $symbol = Symbol::query()->find($this->symbolId);
        $dream = Dream::query()->find($this->dreamId);

        if (! $symbol || ! $dream || filled($symbol->featured_image)) {
            return;
        }

        try {
            $symbolImage = $openAiDreamService->generateSymbolImage(
                $symbol->title,
                $symbol->description,
                $dream->dream_content,
            );

            if ($symbolImage) {
                $symbol->update(['featured_image' => $symbolImage]);
            }
        } catch (\Throwable $e) {
            Log::error('Symbol image generation failed', [
                'dream_id' => $this->dreamId,
                'symbol_id' => $this->symbolId,
                'symbol_title' => $symbol->title,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
