<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind application services here as the domain layer grows.
    }

    public function boot(): void
    {
        // Register shared view or application behavior here.
    }
}
