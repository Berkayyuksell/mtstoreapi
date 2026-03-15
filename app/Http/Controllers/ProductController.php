<?php

namespace App\Http\Controllers;

use App\Services\NebimApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/products
     *
     * ?Barcode=XXXX   → tek ürün barkoda göre ara, bulunamazsa status:false
     * ?Product=XX     → ürün kodu veya adında arama yap (min 2 karakter), ilk 10 sonuç
     */
    public function getProductList(Request $request): JsonResponse
    {
        try {
            $client = NebimApiClient::fromProject($request->user()->project);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }

        // --- Barcode search ---
        if ($request->filled('Barcode')) {
            $result = $client->get('getItems', [
                'Barcode'  => $request->input('Barcode'),
                'LangCode' => 'TR',
            ]);

            if (! $result['ok']) {
                return response()->json(['status' => false, 'message' => $result['error'], 'data' => null], 502);
            }

            if (empty($result['data'])) {
                return response()->json(['status' => false, 'data' => null]);
            }

            return response()->json(['status' => true, 'data' => $result['data'][0]]);
        }

        // --- Product search ---
        if ($request->filled('Product')) {
            $value = $request->input('Product');

            if (mb_strlen($value) < 2) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Arama için en az 2 karakter giriniz.',
                    'data'    => [],
                ]);
            }

            $result = $client->get('getAllItems', [
                'Value'    => $value,
                'LangCode' => 'TR',
            ]);

            if (! $result['ok']) {
                return response()->json(['status' => false, 'message' => $result['error'], 'data' => []], 502);
            }

            return response()->json([
                'status' => true,
                'data'   => array_slice($result['data'], 0, 10),
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => 'Barcode veya Product parametresi gerekli.',
            'data'    => null,
        ], 400);
    }

    public function getProductDetail(Request $request): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'Not implemented.'], 501);
    }
}
