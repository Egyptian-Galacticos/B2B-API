<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class LogUserRegistered
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
    public function handle(Registered $event): void
    {
        echo "LogUserRegistered\n";
        $user = $event->user;

        Log::channel('audit')->info('auth.register', [
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'metadata' => ['email' => $user->email],
        ]);
    }
}
