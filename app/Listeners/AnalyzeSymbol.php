<?php

namespace App\Listeners;

use App\Events\DreamCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AnalyzeSymbol
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DreamCreated $event): void
    {
        // http request to a web hook
    }
}
