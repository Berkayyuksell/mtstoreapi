<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Office;
use App\Models\Project;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\NebimApiClient;

class SyncService
{
    public function sync(Project $project): array
    {
        $client = NebimApiClient::fromProject($project, timeout: 30);

        $counts = [
            'companies'  => 0,
            'offices'    => 0,
            'stores'     => 0,
            'warehouses' => 0,
        ];

        // --- Companies ---
        $companiesData = $this->fetchEndpoint($client, 'getCompanies');

        foreach ($companiesData as $item) {
            Company::updateOrCreate(
                [
                    'project_id'  => $project->id,
                    'CompanyCode' => $item['CompanyCode'],
                ],
                [
                    'CompanyName' => $item['CompanyName'],
                ]
            );
            $counts['companies']++;
        }

        // Build CompanyCode → id map for this project
        $companyMap = Company::where('project_id', $project->id)
            ->pluck('id', 'CompanyCode');

        // --- Offices ---
        $officesData = $this->fetchEndpoint($client, 'getOffices');

        foreach ($officesData as $item) {
            $companyId = $companyMap[$item['CompanyCode']] ?? null;
            if (! $companyId) {
                continue;
            }

            Office::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'OfficeCode' => $item['OfficeCode'],
                ],
                [
                    'OfficeName' => $item['OfficeDescription'],
                ]
            );
            $counts['offices']++;
        }

        // Build (CompanyCode_OfficeCode) → office_id map
        $officeRows = Office::whereIn('company_id', $companyMap->values())->get();
        $officeMap  = [];
        foreach ($officeRows as $office) {
            $companyCode = $companyMap->search($office->company_id);
            if ($companyCode !== false) {
                $officeMap[$companyCode . '_' . $office->OfficeCode] = $office->id;
            }
        }

        // --- Stores ---
        $storesData = $this->fetchEndpoint($client, 'getStores');

        foreach ($storesData as $item) {
            $key      = ($item['CompanyCode'] ?? '') . '_' . ($item['OfficeCode'] ?? '');
            $officeId = $officeMap[$key] ?? null;
            if (! $officeId) {
                continue;
            }

            Store::updateOrCreate(
                [
                    'office_id' => $officeId,
                    'StoreCode' => $item['StoreCode'],
                ],
                [
                    'StoreName' => $item['StoreName'],
                ]
            );
            $counts['stores']++;
        }

        // --- Warehouses ---
        $warehousesData = $this->fetchEndpoint($client, 'getWarehouses');

        foreach ($warehousesData as $item) {
            $key      = ($item['CompanyCode'] ?? '') . '_' . ($item['OfficeCode'] ?? '');
            $officeId = $officeMap[$key] ?? null;
            if (! $officeId) {
                continue;
            }

            Warehouse::updateOrCreate(
                [
                    'office_id'    => $officeId,
                    'WareHouseCode' => $item['WarehouseCode'],
                ],
                [
                    'WareHouseName' => $item['WarehouseName'],
                ]
            );
            $counts['warehouses']++;
        }

        return $counts;
    }

    private function fetchEndpoint(NebimApiClient $client, string $endpoint): array
    {
        $result = $client->get($endpoint);

        if (! $result['ok']) {
            throw new \RuntimeException($result['error']);
        }

        return $result['data'];
    }
}
