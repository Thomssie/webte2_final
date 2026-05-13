<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define a callback that returns the relative URL.
     */
    public function urlResolver(): ?Closure
    {
        return function (Request $request): string {
            $basePath = rtrim(parse_url((string) config('app.url'), PHP_URL_PATH) ?: '', '/');
            $requestUri = $request->getRequestUri();

            // Nginx odovzdava Laravelu REQUEST_URI bez podadresara, ale Inertia ho potrebuje.
            if ($basePath === '' || $requestUri === $basePath || str_starts_with($requestUri, "{$basePath}/")) {
                return $requestUri;
            }

            return $basePath . ($requestUri === '/' ? '/' : $requestUri);
        };
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
        ];
    }
}
