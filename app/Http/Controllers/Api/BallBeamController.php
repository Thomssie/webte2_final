<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CasLog;
use App\Services\OctaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AnimationUsageService;
use App\Services\IpGeolocationService;

class BallBeamController extends Controller
{
    // Spracuje poziadavku na vypocet simulacie gulicky na tyci cez Octave.
    // Pouziva sa v routes/api.php pre endpoint POST /api/simulations/ball-beam.
    public function simulate(
        Request $request,
        OctaveService $octave,
        AnimationUsageService $usageService,
        IpGeolocationService $geolocation
    ): JsonResponse
    {
        $validated = $request->validate([
            'initial_position' => ['required', 'numeric', 'min:-0.5', 'max:0.5'],
            'initial_velocity' => ['sometimes', 'numeric'],
            'initial_angle' => ['sometimes', 'numeric'],
            'target_position' => ['required', 'numeric', 'min:-0.5', 'max:0.5'],
            'duration' => ['required', 'numeric', 'min:1', 'max:30'],
        ]);

        $initialPosition = (float) $validated['initial_position'];
        $initialVelocity = (float) ($validated['initial_velocity'] ?? 0);
        $initialAngle = deg2rad((float) ($validated['initial_angle'] ?? 0));
        $targetPosition = (float) $validated['target_position'];
        $duration = (float) $validated['duration'];

        $script = <<<OCTAVE
pkg load control;

m = 0.111;
R = 0.015;
g = -9.8;
J = 9.99e-6;
H = -m*g/(J/(R^2)+m);
A = [0 1 0 0; 0 0 H 0; 0 0 0 1; 0 0 0 0];
B = [0;0;0;1];
C = [1 0 0 0];
D = [0];
K = place(A,B,[-2+2i,-2-2i,-20,-80]);
N = -inv(C*inv(A-B*K)*B);

sys = ss(A-B*K,B*N,C,D);

t = 0:0.01:{$duration};
r = {$targetPosition};
[y,t,x] = lsim(sys,r*ones(size(t)),t,[{$initialPosition};{$initialVelocity};{$initialAngle};0]);

for i = 1:length(t)
    printf("%.5f,%.8f,%.8f\\n", t(i), y(i), x(i,3));
endfor
OCTAVE;

        $result = $octave->run($script);

        // Kazde volanie Octave z animacie logujeme pre CSV export a kontrolu chyb.
        CasLog::create([
            'source' => 'simulation_ball_beam',
            'command' => $script,
            'success' => $result['success'],
            'output' => $result['output'],
            'error_message' => $result['error'],
            'ip_address' => CasLog::hashIp($request->ip()),
        ]);

        if (! $result['success']) {
            return response()->json([
                'ok' => false,
                'error' => $result['error'],
                'output' => $result['output'],
            ], 422);
        }

        $time = [];
        $position = [];
        $angle = [];
        // Vystup z Octave rozdelime na riadky, kde kazdy riadok obsahuje cas, polohu a uhol.
        $lines = preg_split('/\r\n|\r|\n/', trim($result['output']));

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $values = array_map('trim', explode(',', $line));

            if (count($values) !== 3) {
                continue;
            }

            $time[] = (float) $values[0];
            $position[] = (float) $values[1];
            $angle[] = (float) $values[2];
        }

        $response = response()->json([
            'ok' => true,
            'time' => $time,
            'position' => $position,
            'angle' => $angle,
        ]);

        $cookie = $usageService->record($request, 'ball_beam', $geolocation);

        if ($cookie) {
            $response->withCookie($cookie);
        }

        return $response;

    }
}
