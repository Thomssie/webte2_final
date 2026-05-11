<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnimationUsage;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
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
