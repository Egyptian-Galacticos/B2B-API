<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class LogUserLogin
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
    public function handle(Login $event): void
    {
        $user = $event->user;

        Log::channel('audit')->info('auth.login', [
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'metadata' => ['email' => $user->email],
        ]);
    }
}
