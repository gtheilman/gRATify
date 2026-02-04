<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOperationalSignalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('web');
        $role = $user?->role;
        $normalizedRole = $role === 'poobah' ? 'admin' : $role;
        if ($normalizedRole !== 'admin') {
            return $this->errorResponse('forbidden', null, 403);
        }

        $windowMinutes = 15;
        $metrics = ['auth_me_401', 'attempts_429'];
        $series = [];
        $totals = [];

        foreach ($metrics as $metric) {
            $points = [];
            $total = 0;

            for ($i = $windowMinutes - 1; $i >= 0; $i -= 1) {
                $minute = now()->subMinutes($i);
                $bucket = $minute->format('YmdHi');
                $count = (int) cache()->get("opsig.{$metric}.{$bucket}", 0);
                $total += $count;
                $points[] = [
                    'minute' => $minute->toIso8601String(),
                    'count' => $count,
                ];
            }

            $series[$metric] = $points;
            $totals[$metric] = $total;
        }

        return response()->json([
            'window_minutes' => $windowMinutes,
            'generated_at' => now()->toIso8601String(),
            'totals' => $totals,
            'series' => $series,
        ]);
    }
}
