<?php

namespace App\Providers;

use App\Services\TelegramService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('whatsapp', function ($app) {
            return new WhatsAppService(config('services.whatsapp.base_url'));
        });

        $this->app->singleton(TelegramService::class, function ($app) {
            return new TelegramService;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Inertia::share([
            'environment' => fn () => app()->environment(),
        ]);
    }
}
