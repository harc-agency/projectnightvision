<?php

namespace App\Jobs;

use App\Models\Dream;
use App\Models\Symbol;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
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
    public function handle()
    {
        Log::info('AnalyzeSymbolsJob started for dream', ['dream_id' => $this->dream->id]);

        try {
            // Get existing symbols
            $existingSymbols = Symbol::all()->map(function ($symbol) {
                return [
                    'title' => $symbol->title,
                    'description' => $symbol->description,
                ];
            })->toArray();

            Log::info('Fetched existing symbols', ['count' => count($existingSymbols)]);

            // Prepare the payload for the webhook
            $webhookUrl = rtrim(env('N8N_URL'), '/') . '/webhook/symbol';
            $payload = [
                'dream_content' => $this->dream->dream_content,
                'pre_existing_symbols' => $existingSymbols,
            ];

            Log::info('Sending request to webhook', ['url' => $webhookUrl, 'payload' => $payload]);

            // Send the request to the AI webhook
            $response = Http::post($webhookUrl, $payload);

            // Log the raw response
            Log::info('Received response from webhook', ['status' => $response->status(), 'body' => $response->body()]);

            // Check if the response is successful
            if (!$response->successful()) {
                Log::error('Webhook request failed', ['status' => $response->status(), 'body' => $response->body()]);
                return;
            }

            $symbolsData = $response->json('symbols');

            if (empty($symbolsData)) {
                Log::warning('No symbols returned from the webhook');
                return;
            }

            Log::info('Symbols received from webhook', ['symbols' => $symbolsData]);

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
