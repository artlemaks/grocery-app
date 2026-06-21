<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $db = false;

        try {
            DB::connection()->getPdo();
            $db = true;
        } catch (\Throwable $e) {
            $db = false;
        }

        return response()->json([
            'status' => 'ok',
            'service' => 'larder-api',
            'time' => now()->toIso8601String(),
            'db' => $db,
        ]);
    }
}
