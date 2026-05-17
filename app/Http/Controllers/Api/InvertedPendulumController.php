<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CasLog;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use App\Services\OctaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AnimationUsageService;
use App\Services\IpGeolocationService;

// Metadata pre Scramble zaradia simulacny endpoint do sekcie simulacii.
#[Group('Simulations', 'Výpočty dát pre synchronizované animácie')]
class InvertedPendulumController extends Controller
{
    // Spracuje poziadavku na vypocet simulacie inverzneho kyvadla cez Octave.
    // Pouziva sa v routes/api.php pre endpoint POST /api/simulations/inverted-pendulum.
    #[Endpoint(title: 'Výpočet simulácie inverzného kyvadla', description: 'Vráti čas, polohu a uhol pre animáciu inverzného kyvadla.')]
    public function simulate(
        Request $request,
        OctaveService $octave,
        AnimationUsageService $usageService,
        IpGeolocationService $geolocation
    ): JsonResponse
    {
        $validated = $request->validate([
            'initial_position' => ['required', 'numeric', 'min:-100', 'max:100'],
            'initial_velocity' => ['sometimes', 'numeric', 'min:-10', 'max:10'],
            'initial_angle' => ['required', 'numeric', 'min:-45', 'max:45'],
            'initial_angular_velocity' => ['sometimes', 'numeric', 'min:-10', 'max:10'],
            'target_position' => ['required', 'numeric', 'min:-100', 'max:100'],
            'duration' => ['required', 'numeric', 'min:1', 'max:30'],
        ]);

        $initialPosition = (float) $validated['initial_position'];
        $initialVelocity = (float) ($validated['initial_velocity'] ?? 0);
        $initialAngle = deg2rad((float) $validated['initial_angle']);
        $initialAngularVelocity = (float) ($validated['initial_angular_velocity'] ?? 0);
        $targetPosition = (float) $validated['target_position'];
        $duration = (float) $validated['duration'];

        // Octave skript vychádza z modelu prevráteného kyvadla zo zadania.
        $script = <<<OCTAVE
pkg load control;

M = .5;
m = 0.2;
b = 0.1;
I = 0.006;
g = 9.8;
l = 0.3;
p = I*(M+m)+M*m*l^2;

A = [0 1 0 0; 0 -(I+m*l^2)*b/p (m^2*g*l^2)/p 0; 0 0 0 1; 0 -(m*l*b)/p m*g*l*(M+m)/p 0];
B = [0; (I+m*l^2)/p; 0; m*l/p];
C = [1 0 0 0; 0 0 1 0];
D = [0; 0];

K = lqr(A,B,C'*C,1);
Ac = [(A-B*K)];
N = -inv(C(1,:)*inv(A-B*K)*B);

sys = ss(Ac,B*N,C,D);

t = 0:0.05:{$duration};
r = {$targetPosition};

[y,t,x] = lsim(sys,r*ones(size(t)),t,[{$initialPosition};{$initialVelocity};{$initialAngle};{$initialAngularVelocity}]);

for i = 1:length(t)
    printf("%.5f,%.8f,%.8f\\n", t(i), y(i,1), y(i,2));
endfor
OCTAVE;

        $result = $octave->run($script);

        // Kazde volanie Octave z animacie logujeme pre CSV export a kontrolu chyb.
        CasLog::create([
            'source' => 'simulation_inverted_pendulum',
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

        $cookie = $usageService->record($request, 'inverted_pendulum', $geolocation);

        if ($cookie) {
            $response->withCookie($cookie);
        }

        return $response;

    }
}
