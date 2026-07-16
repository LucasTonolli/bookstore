<?php

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;

class DeleteUserTokens
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
    public function handle(PasswordReset $event): void
    {
        $event->user->tokens()->delete();
    }
}
