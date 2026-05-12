<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnimationUsage;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Illuminate\Http\JsonResponse;

// Metadata pre Scramble zaradia statisticke endpointy do spolocnej sekcie dokumentacie.
#[Group('Statistics', 'Statistiky pouzivania animacii')]
class StatisticsController extends Controller
{
    // Endpoint vrati pocty pouziti jednotlivych animacii.
    #[Endpoint(title: 'Suhrn pouziti animacii', description: 'Vrati agregovany pocet spusteni pre kazdy typ animacie.')]
    public function summary(): JsonResponse
    {
        $summary = AnimationUsage::query()
            ->selectRaw('animation_type, count(*) as count')
            ->groupBy('animation_type')
            ->get();

        return response()->json([
            'ok' => true,
            'summary' => $summary,
        ]);
    }

    // Endpoint vrati detailne zaznamy pre jeden typ animacie.
    #[Endpoint(title: 'Detail pouzitia konkretnej animacie', description: 'Vrati jednotlive zaznamy pouzitia pre vybrany typ animacie.')]
    #[PathParameter('animationType', description: 'Typ animacie.', type: 'string', example: 'ball_beam')]
    public function details(string $animationType): JsonResponse
    {
        $usages = AnimationUsage::query()
            ->where('animation_type', $animationType)
            ->latest()
            ->get([
                'id',
                'animation_type',
                'city',
                'country',
                'created_at',
            ]);

        return response()->json([
            'ok' => true,
            'animation_type' => $animationType,
            'usages' => $usages,
        ]);
    }
}
