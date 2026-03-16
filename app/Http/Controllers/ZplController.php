<?php

namespace App\Http\Controllers;

use App\Services\ZplService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZplController extends Controller
{
    public function __construct(private readonly ZplService $zplService) {}

    // -----------------------------------------------------------------------
    // POST /api/zpl/label
    // Body: { "barcodes": "8680001234567" }   (virgülle birden fazla)
    // -----------------------------------------------------------------------

    public function fetchBarcodeLabel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcodes' => 'required|string',
        ]);

        /** @var \App\Models\User $user */
        $user   = $request->user();
        $result = $this->zplService->generateLabels($user, $validated['barcodes']);

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'log'     => $result['log'],
            ], 422);
        }

        return response()->json([
            'success'   => true,
            'inventory' => $result['inventory'],
            'zpl'       => $result['zpl'],
            'count'     => count($result['zpl']),
            'log'       => $result['log'],
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /api/zpl/templates
    // -----------------------------------------------------------------------

    public function listTemplates(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $templates = \App\Models\ZplLabelTemplate::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('project_id', $user->project_id)
                  ->orWhereNull('project_id');
            })
            ->orderByRaw('project_id IS NULL ASC')
            ->get(['id', 'template_code', 'template_name', 'variables']);

        return response()->json(['success' => true, 'templates' => $templates]);
    }
}
