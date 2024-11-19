<?php

namespace App\Listeners;

use App\Events\DreamCreated;
use Illuminate\Support\Facades\Http;

class AnalyzeDream
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(DreamCreated $event): void
    {

        dd(
            $event
        );

        $url = env('N8N_URL').'/webhook/dream';
        // http request to a web hook env('N8N_URL')
        $request = Http::post($url, [
            'content' => $event->dream_content,
        ]);

        //$request

    }
}
