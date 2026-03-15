<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\NebimApiClient;
use App\Services\SyncService;
use Filament\Pages\Page;

class ApiTester extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static string $view = 'filament.pages.api-tester';
    protected static ?string $navigationLabel = 'API Test';
    protected static ?string $title = 'API Test Paneli';
    protected static ?int $navigationSort = 99;

    public ?int $userId = null;
    public string $endpointKey = '';
    public array $paramValues = [];
    public ?string $responseJson = null;
    public ?int $responseStatus = null;
    public ?float $responseTime = null;
    public bool $isLoading = false;
    public ?string $errorMessage = null;

    // -------------------------------------------------------
    // Endpoint tanımları
    // -------------------------------------------------------
    public function getEndpointGroups(): array
    {
        return [
            'Ürün' => [
                'products.barcode' => [
                    'label'  => 'Barkod ile Ürün Ara',
                    'method' => 'GET',
                    'params' => [
                        ['key' => 'Barcode', 'label' => 'Barkod', 'required' => true],
                    ],
                ],
                'products.search' => [
                    'label'  => 'Ürün Adı / Kodu ile Ara',
                    'method' => 'GET',
                    'params' => [
                        ['key' => 'Product', 'label' => 'Ürün Kodu veya Adı (min 2 karakter)', 'required' => true],
                    ],
                ],
            ],
            'Nebim Direkt' => [
                'nebim.companies' => [
                    'label'  => 'Şirketler',
                    'method' => 'GET',
                    'params' => [],
                ],
                'nebim.offices' => [
                    'label'  => 'Ofisler',
                    'method' => 'GET',
                    'params' => [],
                ],
                'nebim.stores' => [
                    'label'  => 'Mağazalar',
                    'method' => 'GET',
                    'params' => [],
                ],
                'nebim.warehouses' => [
                    'label'  => 'Depolar',
                    'method' => 'GET',
                    'params' => [],
                ],
                'nebim.inventory' => [
                    'label'  => 'Stok Sorgula',
                    'method' => 'GET',
                    'params' => [
                        ['key' => 'value',     'label' => 'Arama Değeri',  'required' => false],
                        ['key' => 'company',   'label' => 'Şirket Kodu',   'required' => false],
                        ['key' => 'office',    'label' => 'Ofis Kodu',     'required' => false],
                        ['key' => 'store',     'label' => 'Mağaza Kodu',   'required' => false],
                        ['key' => 'warehouse', 'label' => 'Depo Kodu',     'required' => false],
                    ],
                ],
                'nebim.price' => [
                    'label'  => 'Fiyat Sorgula',
                    'method' => 'GET',
                    'params' => [
                        ['key' => 'barcode',           'label' => 'Barkod',                   'required' => true],
                        ['key' => 'priceGroupCode',    'label' => 'Fiyat Grubu Kodu',         'required' => true],
                        ['key' => 'discPriceGroupCode','label' => 'İndirim Fiyat Grubu Kodu', 'required' => true],
                        ['key' => 'langCode',          'label' => 'Dil Kodu (TR)',            'required' => false],
                    ],
                ],
                'nebim.items' => [
                    'label'  => 'Ürün Detayı (Barkod)',
                    'method' => 'GET',
                    'params' => [
                        ['key' => 'Barcode',  'label' => 'Barkod',   'required' => true],
                        ['key' => 'LangCode', 'label' => 'Dil Kodu', 'required' => false],
                    ],
                ],
            ],
            'Senkronizasyon' => [
                'sync' => [
                    'label'  => 'Projeyi Senkronize Et',
                    'method' => 'POST',
                    'params' => [],
                ],
            ],
        ];
    }

    public function getUsers(): array
    {
        return User::orderBy('name')
            ->get()
            ->mapWithKeys(fn($u) => [$u->id => "{$u->name} ({$u->email})"])
            ->toArray();
    }

    public function getSelectedEndpointConfig(): ?array
    {
        foreach ($this->getEndpointGroups() as $endpoints) {
            if (isset($endpoints[$this->endpointKey])) {
                return $endpoints[$this->endpointKey];
            }
        }
        return null;
    }

    public function updatedEndpointKey(): void
    {
        $this->paramValues  = [];
        $this->responseJson = null;
        $this->responseStatus = null;
        $this->responseTime   = null;
        $this->errorMessage   = null;
    }

    // -------------------------------------------------------
    // İstek gönder
    // -------------------------------------------------------
    public function sendRequest(): void
    {
        $this->errorMessage   = null;
        $this->responseJson   = null;
        $this->responseStatus = null;
        $this->responseTime   = null;

        if (! $this->userId) {
            $this->errorMessage = 'Lütfen bir kullanıcı seçin.';
            return;
        }

        if (! $this->endpointKey) {
            $this->errorMessage = 'Lütfen bir endpoint seçin.';
            return;
        }

        $user = User::with('project')->find($this->userId);

        if (! $user || ! $user->project) {
            $this->errorMessage = 'Seçilen kullanıcıya ait proje bulunamadı.';
            return;
        }

        $startTime = microtime(true);

        try {
            $result = $this->executeEndpoint($user->project);
            $this->responseStatus = $result['status'];
            $this->responseJson   = json_encode($result['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            $this->errorMessage   = $e->getMessage();
            $this->responseStatus = 500;
        }

        $this->responseTime = round((microtime(true) - $startTime) * 1000, 1);
    }

    private function executeEndpoint(\App\Models\Project $project): array
    {
        $params = array_filter(
            $this->paramValues,
            fn($v) => $v !== '' && $v !== null
        );

        // ---------- Ürün (Laravel katmanı) ----------
        if ($this->endpointKey === 'products.barcode') {
            $client = NebimApiClient::fromProject($project);
            $result = $client->get('getItems', array_merge(['LangCode' => 'TR'], $params));
            if (! $result['ok']) {
                return ['status' => 502, 'data' => ['error' => $result['error']]];
            }
            return ['status' => 200, 'data' => empty($result['data']) ? ['status' => false, 'data' => null] : ['status' => true, 'data' => $result['data'][0]]];
        }

        if ($this->endpointKey === 'products.search') {
            $client = NebimApiClient::fromProject($project);
            $result = $client->get('getAllItems', array_merge(['LangCode' => 'TR'], $params));
            if (! $result['ok']) {
                return ['status' => 502, 'data' => ['error' => $result['error']]];
            }
            return ['status' => 200, 'data' => ['status' => true, 'data' => array_slice($result['data'], 0, 10)]];
        }

        // ---------- Senkronizasyon ----------
        if ($this->endpointKey === 'sync') {
            $counts = app(SyncService::class)->sync($project);
            return ['status' => 200, 'data' => ['success' => true, 'data' => $counts]];
        }

        // ---------- Nebim direkt ----------
        $nebimMap = [
            'nebim.companies'  => 'getCompanies',
            'nebim.offices'    => 'getOffices',
            'nebim.stores'     => 'getStores',
            'nebim.warehouses' => 'getWarehouses',
            'nebim.inventory'  => 'getInventory',
            'nebim.price'      => 'getPrice',
            'nebim.items'      => 'getItems',
        ];

        if (isset($nebimMap[$this->endpointKey])) {
            $client = NebimApiClient::fromProject($project);
            $result = $client->get($nebimMap[$this->endpointKey], $params);
            return [
                'status' => $result['ok'] ? 200 : 502,
                'data'   => $result['ok'] ? $result['data'] : ['error' => $result['error']],
            ];
        }

        throw new \RuntimeException('Bilinmeyen endpoint: ' . $this->endpointKey);
    }
}
