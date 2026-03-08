<?php

namespace App\Listeners;

use App\Events\DreamCreated;

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
        // Legacy event hook retained for compatibility.
    }
}
