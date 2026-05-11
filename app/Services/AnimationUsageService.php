<?php

namespace App\Services;

use App\Models\AnimationUsage;
use App\Models\CasLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class AnimationUsageService
{
    public function record(Request $request, string $animationType, IpGeolocationService $geolocation): ?Cookie
    {
        $cookieName = 'animation_visitor_token';
        $visitorToken = $request->cookie($cookieName);
        $newCookie = null;

        if (! $visitorToken) {
            $visitorToken = (string) Str::uuid();
            $newCookie = cookie($cookieName, $visitorToken, 60 * 24 * 365);
        }

        $intervalMinutes = config('cas.animation_stats_interval_minutes');

        $lastUsage = AnimationUsage::query()
            ->where('animation_type', $animationType)
            ->where('visitor_token', $visitorToken)
            ->latest()
            ->first();

        if ($lastUsage && $lastUsage->created_at->gt(now()->subMinutes($intervalMinutes))) {
            return $newCookie;
        }

        $location = $geolocation->lookup($request->ip());
        AnimationUsage::create([
            'animation_type' => $animationType,
            'visitor_token' => $visitorToken,
            'ip_hash' => CasLog::hashIp($request->ip()),
            'city' => $location['city'],
            'country' => $location['country'],
        ]);

        return $newCookie;
    }
}
