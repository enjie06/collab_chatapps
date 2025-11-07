<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

public function boot(): void
{
    Event::listen(Login::class, function ($event) {
        $event->user->update(['is_online' => true]);
    });

    Event::listen(Logout::class, function ($event) {
        $event->user->update(['is_online' => false]);
    });
}
