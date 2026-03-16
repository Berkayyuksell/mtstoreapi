<?php

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use App\Models\ZplLabelTemplate;
use Illuminate\Support\Facades\Http;

class ZplService
{
    /**
     * Kullanıcı için barkod(lar)dan ZPL etiket üretir.
     *
     * @return array{ok: bool, zpl: list<string>, inventory: list<array>, log: list<string>, error: string|null}
     */
    public function generateLabels(User $user, string $rawBarcodes): array
    {
        $project = $user->project;

        if (! $project) {
            return $this->fail('Kullanıcıya proje tanımlanmamış');
        }

        $template = $user->resolvedZplTemplate();

        if (! $template) {
            return $this->fail('Kullanıcıya veya projeye ait ZPL template bulunamadı');
        }

        $priceGroupCode     = $user->resolvedPriceGroupCode();
        $discPriceGroupCode = $user->resolvedDiscPriceGroupCode();
        $barcodes           = $this->parseBarcodes($rawBarcodes);

        $zplList       = [];
        $inventoryList = [];
        $log           = [];

        foreach ($barcodes as $barcode) {
            $result = $this->fetchPrice($project, $barcode, $priceGroupCode, $discPriceGroupCode);

            if (! $result['ok']) {
                $log[] = "Barcode {$barcode}: {$result['error']}";
                continue;
            }

            $item            = $result['data'];
            $variableMap     = $this->buildVariableMap($item, $user->store?->StoreName ?? '');
            $zplList[]       = $this->render($template->zpl_template, $variableMap);
            $inventoryList[] = $item;
        }

        if (empty($zplList)) {
            return [
                'ok'        => false,
                'zpl'       => [],
                'inventory' => [],
                'log'       => $log,
                'error'     => 'Hiç etiket üretilemedi',
            ];
        }

        return [
            'ok'        => true,
            'zpl'       => $zplList,
            'inventory' => $inventoryList,
            'log'       => $log,
            'error'     => null,
        ];
    }

    // -----------------------------------------------------------------------
    // Helpers (iç kullanım için public — ApiTester'dan da erişilebilir)
    // -----------------------------------------------------------------------

    /**
     * Nebim /getPrice endpoint'inden ham veri çeker.
     *
     * @return array{ok: bool, data: array|null, error: string|null}
     */
    public function fetchPrice(
        \App\Models\Project $project,
        string $barcode,
        string $priceGroupCode,
        string $discPriceGroupCode,
    ): array {
        $url = rtrim($project->project_api_address, '/') . '/getPrice';

        try {
            $response = Http::timeout(30)
                ->withBasicAuth(
                    $project->project_api_username ?? '',
                    $project->project_api_password ?? '',
                )
                ->get($url, [
                    'barcode'            => $barcode,
                    'priceGroupCode'     => $priceGroupCode,
                    'discPriceGroupCode' => $discPriceGroupCode,
                ]);

            if (! $response->successful()) {
                return ['ok' => false, 'data' => null, 'error' => 'HTTP ' . $response->status()];
            }

            $data = $response->json();

            if (! is_array($data) || empty($data)) {
                return ['ok' => false, 'data' => null, 'error' => 'API\'den veri gelmedi'];
            }

            return ['ok' => true, 'data' => isset($data[0]) ? $data[0] : $data, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * API satırını {{VariableName}} map'ine dönüştürür.
     */
    public function buildVariableMap(array $item, string $storeName = ''): array
    {
        $productPrice         = number_format((float) ($item['ProductPrice'] ?? 0), 2, ',', '.');
        $productDiscountPrice = number_format((float) ($item['ProductDiscountPrice'] ?? 0), 2, ',', '.');
        $priceValidDate       = ! empty($item['PriceValidDate'])
            ? date('d.m.Y', strtotime($item['PriceValidDate']))
            : '';

        $hasDiscount        = ($item['ProductPrice'] ?? 0) > ($item['ProductDiscountPrice'] ?? 0);
        $strikethroughBlock = $hasDiscount
            ? "^FO10,53^GB180,1,1^FS\n^FO10,55^GB180,1,1^FS"
            : '';

        return [
            'ItemCode'             => $item['ItemCode']         ?? '',
            'ColorCode'            => $item['ColorCode']        ?? '',
            'ItemDescription'      => $item['ItemDescription']  ?? '',
            'ColorDescription'     => $item['ColorDescription'] ?? '',
            'ItemDim1Code'         => $item['ItemDim1Code']     ?? '',
            'ProductPrice'         => $productPrice,
            'ProductDiscountPrice' => $productDiscountPrice,
            'PriceValidDate'       => $priceValidDate,
            'IsDomestic'           => (string) ($item['IsDomestic'] ?? 0),
            'Barcode'              => substr($item['Barcode'] ?? '', 0, 12),
            'Hierarchy'            => $item['Hierarchy']        ?? '',
            'Atr12'                => $item['Atr12']            ?? '',
            'Currency'             => $item['Currency']         ?? 'TRY',
            'StoreName'            => $storeName,
            'StrikethroughBlock'   => $strikethroughBlock,
        ];
    }

    /**
     * {{VariableName}} yer tutucularını değerlerle doldurur + Türkçe fix.
     */
    public function render(string $zplTemplate, array $variables): string
    {
        $search  = array_map(fn ($k) => '{{' . $k . '}}', array_keys($variables));
        $replace = array_values($variables);

        $zpl = str_replace($search, $replace, $zplTemplate);

        $turkish = ['ç', 'Ç', 'ş', 'Ş', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ı', 'İ'];
        $safe    = ['c', 'C', 's', 'S', 'g', 'G', 'u', 'U', 'o', 'O', 'i', 'I'];

        return str_replace($turkish, $safe, $zpl);
    }

    // -----------------------------------------------------------------------

    private function parseBarcodes(string $raw): array
    {
        return array_values(
            array_filter(array_map('trim', explode(',', $raw)))
        );
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'zpl' => [], 'inventory' => [], 'log' => [], 'error' => $error];
    }
}
