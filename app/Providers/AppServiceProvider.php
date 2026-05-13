<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Pri nasadeni v podadresari musi Laravel generovat URL podla APP_URL.
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        // Produkcny server bezi za HTTPS Nginx konfiguraciou.
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Scramble dokumentuje iba skutocne backend API routy z routes/api.php.
        // Bez tejto filtracie by sa do OpenAPI dostali aj web routy zacinajuce na api-docs.
        Scramble::configure()->routes(function (Route $route): bool {
            return str_starts_with($route->uri(), 'api/');
        });

        // Vsetky API endpointy su chranene rovnakym X-API-Key headerom.
        // Security scheme sa doplni do vygenerovanej OpenAPI specifikacie aj do PDF exportu.
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->secure(
                SecurityScheme::apiKey('header', 'X-API-Key')
                    ->as('ApiKeyAuth')
                    ->setDescription('API kluc definovany v konfiguracii aplikacie.')
            );
        });
    }
}
