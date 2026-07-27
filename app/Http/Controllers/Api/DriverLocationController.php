<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateDriverLocationRequest;
use App\Services\DriverLocationService;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Business Purpose: استقبال إحداثيات GPS من تطبيق السائق (APK) أثناء العمل بالخلفية.
 */
class DriverLocationController extends Controller
{
    public function update(UpdateDriverLocationRequest $request, DriverLocationService $locations): JsonResponse
    {
        $user = $request->user();

        try {
            $row = $locations->updatePosition(
                (int) $user->id,
                (float) $request->validated('latitude'),
                (float) $request->validated('longitude'),
                $request->filled('accuracy') ? (float) $request->validated('accuracy') : null,
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'recorded_at' => $row->recorded_at?->toIso8601String(),
            'is_sharing' => $row->is_sharing,
        ]);
    }
}
