<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        if ($this->app->environment('local')) {
            // Use a fresh URL to bypass the cached script containing old PHP notices.
            \Livewire\Livewire::setScriptRoute(fn ($handler) =>
                \Illuminate\Support\Facades\Route::get('/livewire/local/livewire.js', $handler)
            );
        }
        \Illuminate\Support\Facades\Gate::define('manage-stocks', fn (\App\Models\User $user) => $user->is_admin);
    }
}
