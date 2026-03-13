<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiDreamService
{
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function analyzeDream(string $dreamContent): array
    {
        $response = $this->request()->post($this->baseUrl.'/chat/completions', [
            'model' => config('services.openai.analysis_model'),
            'temperature' => 0.4,
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You are a dream analysis assistant.',
                        'Return ONLY valid JSON.',
                        'Use this exact shape:',
                        '{"title":"string","overall_theme":"string","analysis":"string","sentiment":"positive|neutral|negative"}',
                        'The sentiment must be one of: positive, neutral, negative.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => $dreamContent,
                ],
            ],
        ]);

        $payload = $this->extractJsonPayload($response);

        $sentiment = strtolower((string) Arr::get($payload, 'sentiment', 'neutral'));
        if (! in_array($sentiment, ['positive', 'neutral', 'negative'], true)) {
            $sentiment = 'neutral';
        }

        return [
            'title' => trim((string) Arr::get($payload, 'title')) ?: Str::limit(trim($dreamContent), 60),
            'overall_theme' => trim((string) Arr::get($payload, 'overall_theme')) ?: null,
            'analysis' => trim((string) Arr::get($payload, 'analysis')) ?: null,
            'sentiment' => $sentiment,
        ];
    }

    public function extractSymbols(string $dreamContent, array $existingSymbols): array
    {
        $targetCount = max((int) config('services.openai.symbol_target_count', 3), 1);

        $response = $this->request()->post($this->baseUrl.'/chat/completions', [
            'model' => config('services.openai.symbol_model'),
            'temperature' => 0.2,
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You identify dream symbols from dream text.',
                        'Return ONLY valid JSON in this shape:',
                        '{"symbols":[{"title":"string","description":"string"}]}',
                        'Return exactly ' . $targetCount . ' symbols.',
                        'Rank them from most central to least central to the dream.',
                        'Use only the top ' . $targetCount . ' strongest symbols in the dream.',
                        'Keep descriptions concise (1 sentence).',
                        'Prefer symbols from the pre-existing library when semantically equivalent.',
                        'If a pre-existing symbol fits, reuse its title exactly.',
                        'Do not return near-duplicate titles that only differ by pluralization, punctuation, or alias formatting.',
                        'Choose one canonical title per concept and avoid slash-separated alternatives such as "Bombs/Explosions".',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'dream_content' => $dreamContent,
                        'pre_existing_symbols' => $existingSymbols,
                    ], JSON_UNESCAPED_SLASHES),
                ],
            ],
        ]);

        $payload = $this->extractJsonPayload($response);
        $symbols = Arr::get($payload, 'symbols', []);

        if (! is_array($symbols)) {
            return [];
        }

        $normalized = [];
        foreach ($symbols as $symbol) {
            if (! is_array($symbol)) {
                continue;
            }

            $title = trim((string) Arr::get($symbol, 'title'));
            $description = trim((string) Arr::get($symbol, 'description'));

            if ($title === '' || $description === '') {
                continue;
            }

            $normalized[] = [
                'title' => Str::limit($title, 120, ''),
                'description' => Str::limit($description, 600, ''),
            ];
        }

        return array_slice($normalized, 0, $targetCount);
    }

    public function generateDreamImage(string $dreamContent, ?string $analysis = null): ?string
    {
        $prompt = trim(implode("\n\n", array_filter([
            'Create a surreal but emotionally coherent dream scene.',
            'Dream narrative:',
            $dreamContent,
            $analysis ? 'Dream analysis context: '.$analysis : null,
            'Style: cinematic, atmospheric, richly detailed, no text overlays.',
        ])));

        return $this->generateImageFromPrompt($prompt);
    }

    public function generateSymbolImage(string $title, ?string $description = null, ?string $dreamContext = null): ?string
    {
        $prompt = trim(implode("\n\n", array_filter([
            'Create a square symbol illustration for Project Night Vision in a uniform celestial-archive style.',
            'Subject: one centered emblem representing "'.$title.'".',
            $description ? 'Meaning context: '.$description : null,
            $dreamContext ? 'Optional dream context: '.Str::limit($dreamContext, 400) : null,
            'Art direction: scholarly occult, antique astronomical engraving mixed with modern icon design. Crisp silhouette, symmetrical or near-symmetrical composition, fine etched linework, subtle low-relief shading, soft halo, minimal background detail.',
            'Color treatment: strict monochrome only. Use a single grayscale palette with black, charcoal, slate, silver, and ivory tonal variation only. No blue, cyan, orange, gold, red, or any accent colors.',
            'Composition rules: single subject only, centered, generous negative space, safe margins, readable at thumbnail size, designed to look strong on a dark interface.',
            'Avoid: photorealism, painterly scenes, multiple objects, landscapes, characters, hands, text, letters, numbers, frames, watermarks, colorful lighting, colorful backgrounds, busy textures.',
        ])));

        return $this->generateImageFromPrompt(
            $prompt,
            (string) config('services.openai.symbol_image_size', '1024x1024'),
            (string) config('services.openai.symbol_image_quality', 'low'),
            'symbol-images/'
        );
    }

    protected function generateImageFromPrompt(
        string $prompt,
        string $size = '1024x1024',
        string $quality = 'auto',
        string $pathPrefix = 'dream-images/',
    ): ?string {
        $response = $this->request()->post($this->baseUrl.'/images/generations', [
            'model' => config('services.openai.image_model'),
            'prompt' => $prompt,
            'size' => $size,
            'quality' => $quality,
        ]);
        $response->throw();

        $b64 = Arr::get($response->json(), 'data.0.b64_json');
        $remoteUrl = Arr::get($response->json(), 'data.0.url');

        if (is_string($b64) && $b64 !== '') {
            $binary = base64_decode($b64, true);

            if ($binary === false) {
                throw new RuntimeException('Failed to decode generated image payload.');
            }

            $path = trim($pathPrefix, '/').'/'.Str::uuid().'.png';
            Storage::disk('public')->put($path, $binary);

            return Storage::url($path);
        }

        if (is_string($remoteUrl) && $remoteUrl !== '') {
            return $remoteUrl;
        }

        return null;
    }

    public function transcribeAudio(UploadedFile $audioFile): string
    {
        $stream = fopen($audioFile->getRealPath(), 'r');

        if ($stream === false) {
            throw new RuntimeException('Unable to read uploaded audio file.');
        }

        try {
            $response = $this->request()
                ->attach('file', $stream, $audioFile->getClientOriginalName())
                ->post($this->baseUrl.'/audio/transcriptions', [
                    'model' => config('services.openai.transcription_model'),
                ]);
        } finally {
            fclose($stream);
        }
        $response->throw();

        $text = trim((string) Arr::get($response->json(), 'text'));

        if ($text === '') {
            throw new RuntimeException('Transcription response did not include text.');
        }

        return $text;
    }

    protected function request()
    {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        return Http::withToken($apiKey)
            ->acceptJson()
            ->connectTimeout(15)
            ->timeout(180)
            ->retry(3, 1500, function (\Exception $exception) {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                if ($exception instanceof RequestException) {
                    $status = $exception->response?->status();

                    return in_array($status, [408, 409, 425, 429, 500, 502, 503, 504], true);
                }

                return false;
            }, true);
    }

    protected function extractJsonPayload(Response $response): array
    {
        $response->throw();

        $content = (string) Arr::get($response->json(), 'choices.0.message.content', '');

        if ($content === '') {
            throw new RuntimeException('OpenAI response content was empty.');
        }

        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $content);
        $decoded = json_decode((string) $cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new RuntimeException('Unable to parse JSON from OpenAI response.');
        }

        return $decoded;
    }
}
