<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    public function syncProject(Project $project): JsonResponse
    {
        try {
            $counts = $this->syncService->sync($project);

            return response()->json([
                'success' => true,
                'message' => 'Senkronizasyon tamamlandı.',
                'data'    => $counts,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
