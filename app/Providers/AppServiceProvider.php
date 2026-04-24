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
        // Livewire temporary upload URLs expire quickly by default (5 min).
        // Use a longer configurable window to reduce failures on slower networks/devices.
        config()->set('livewire.temporary_file_upload.max_upload_time', (int) env('LIVEWIRE_MAX_UPLOAD_TIME', 15));
    }
}
