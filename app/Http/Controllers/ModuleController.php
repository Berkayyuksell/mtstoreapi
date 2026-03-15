<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $modules = $request->user()
            ->modules()
            ->orderBy('name')
            ->get(['modules.id', 'modules.name']);

        return response()->json([
            'status' => true,
            'data'   => $modules,
        ]);
    }
}
