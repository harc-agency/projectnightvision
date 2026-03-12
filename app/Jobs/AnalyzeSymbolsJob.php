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
use Illuminate\Support\Str;

class AnalyzeSymbolsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dream;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Dream $dream
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
            $existingSymbols = Symbol::all()->map(function ($symbol) {
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

            $symbolIds = [];

            foreach ($symbolsData as $symbolData) {
                // Log each symbol being processed
                Log::info('Processing symbol', ['symbol' => $symbolData]);

                // Find or create the symbol
                $symbol = Symbol::firstOrCreate(
                    ['title' => $symbolData['title']],
                    [
                        'description' => $symbolData['description'],
                        'symbol_key' => Str::slug($symbolData['title'], '_'), // Generate a slug as a symbol_key
                    ]
                );

                if (empty($symbol->featured_image)) {
                    try {
                        $symbolImage = $openAiDreamService->generateSymbolImage(
                            $symbol->title,
                            $symbol->description,
                            $this->dream->dream_content,
                        );

                        if ($symbolImage) {
                            $symbol->update(['featured_image' => $symbolImage]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('Symbol image generation failed', [
                            'dream_id' => $this->dream->id,
                            'symbol_id' => $symbol->id,
                            'symbol_title' => $symbol->title,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Collect symbol IDs for pivot table
                $symbolIds[] = $symbol->id;
            }

            // Sync the symbols with the dream
            $this->dream->symbols()->syncWithoutDetaching($symbolIds);

            Log::info('Symbols synced with dream', ['dream_id' => $this->dream->id, 'symbol_ids' => $symbolIds]);

        } catch (\Exception $e) {
            Log::error('AnalyzeSymbolsJob encountered an error', [
                'dream_id' => $this->dream->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        Log::info('AnalyzeSymbolsJob completed for dream', ['dream_id' => $this->dream->id]);
    }
}
