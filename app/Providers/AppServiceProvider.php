<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */

    // public function boot()
    // {
    //     if (Auth::check()) {
    //         Auth::user()->update(['last_seen_at' => now()]);
    //     }
    // }

    public function boot(): void
    {
        if (Auth::check()) {
            Cache::put('user-is-online-' . Auth::id(), true, now()->addMinutes(5));
            Auth::user()->update(['last_seen_at' => now()]);
        }
    }

}
