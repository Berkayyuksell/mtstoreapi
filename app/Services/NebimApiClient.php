<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;

class NebimApiClient
{
    private string $baseUrl;
    private ?string $username;
    private ?string $password;
    private int $timeout;

    public function __construct(string $baseUrl, ?string $username = null, ?string $password = null, int $timeout = 15)
    {
        $this->baseUrl  = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->timeout  = $timeout;
    }

    public static function fromProject(Project $project, int $timeout = 15): self
    {
        if (empty($project->project_api_address)) {
            throw new \RuntimeException('Projeye ait API adresi tanımlı değil.');
        }

        return new self(
            $project->project_api_address,
            $project->project_api_username,
            $project->project_api_password,
            $timeout,
        );
    }

    /**
     * GET isteği atar.
     *
     * @param  string  $endpoint  Nebim micro service endpoint adı (örn: getItems)
     * @param  array   $params    Query parametreleri
     * @return array{ ok: bool, data: array|null, error: string|null }
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->call('GET', $endpoint, $params);
    }

    /**
     * POST isteği atar.
     *
     * @param  string  $endpoint  Nebim micro service endpoint adı
     * @param  array   $body      JSON body
     * @return array{ ok: bool, data: array|null, error: string|null }
     */
    public function post(string $endpoint, array $body = []): array
    {
        return $this->call('POST', $endpoint, $body);
    }

    private function call(string $method, string $endpoint, array $payload): array
    {
        try {
            $http = Http::timeout($this->timeout);

            if ($this->username && $this->password) {
                $http = $http->withBasicAuth($this->username, $this->password);
            }

            if (strtoupper($method) === 'POST') {
                // POST: endpoint query string'de, body JSON olarak gönderilir
                $response = $http->post($this->baseUrl . '?endpoint=' . $endpoint, $payload);
            } else {
                // GET: endpoint dahil tüm parametreler Guzzle'a query array olarak verilir,
                // böylece boş array geçilince mevcut query string silinmez
                $response = $http->get($this->baseUrl, array_merge(['endpoint' => $endpoint], $payload));
            }

            if (! $response->successful()) {
                return [
                    'ok'    => false,
                    'data'  => null,
                    'error' => "{$endpoint} isteği başarısız. HTTP " . $response->status(),
                ];
            }

            $data = $response->json();

            if (! is_array($data)) {
                return [
                    'ok'    => false,
                    'data'  => null,
                    'error' => "{$endpoint} geçersiz JSON yanıtı döndürdü.",
                ];
            }

            return ['ok' => true, 'data' => $data, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }
}
