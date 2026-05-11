<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IpGeolocationService
{


    public function lookup(?string $ipAddress): array
    {
        if (! config('cas.geolocation_enabled')) {
            return $this->emptyLocation();
        }

        if (! $ipAddress || $this->isLocalOrPrivateIp($ipAddress)) {
            return $this->emptyLocation();
        }

        try {
            $response = Http::timeout(config('cas.geolocation_timeout_seconds'))
                ->get("http://ip-api.com/json/{$ipAddress}", [
                    'fields' => 'status,country,city',
                ]);

            if (! $response->ok()) {
                return $this->emptyLocation();
            }

            $data = $response->json();

            if (($data['status'] ?? null) !== 'success') {
                return $this->emptyLocation();
            }

            return [
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
            ];
        } catch (\Throwable) {
            return $this->emptyLocation();
        }
    }


    private function emptyLocation(): array
    {
        return [
            'city' => null,
            'country' => null,
        ];
    }

    private function isLocalOrPrivateIp(string $ipAddress): bool
    {
        return ! filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
