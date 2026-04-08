<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class OdooService
{
    protected Client $client;
    protected string $url;
    protected string $database;
    protected string $username;
    protected string $apiKey;
    protected ?int $uid = null;

    public function __construct()
    {
        $this->url = config('services.odoo.url');
        $this->database = config('services.odoo.database');
        $this->username = config('services.odoo.username');
        $this->apiKey = config('services.odoo.api_key');

        if (!$this->url || !$this->database || !$this->username || !$this->apiKey) {
            throw new \RuntimeException('Odoo configuration incomplete');
        }

        $this->client = new Client([
            'base_uri' => $this->url,
            'timeout' => 30,
            'headers' => ['Content-Type' => 'application/json'],
            'verify' => false,
        ]);
    }

    public function getCustomers(array $fields = ['id', 'name', 'email', 'phone'], ?int $limit = null): array
    {
        $kwargs = ['fields' => $fields];
        if ($limit) {
            $kwargs['limit'] = $limit;
        }

        return $this->execute_kw('res.partner', 'search_read', [[]], $kwargs);
    }

    public function getProducts(array $fields = ['id', 'name', 'default_code', 'list_price'], ?int $limit = null): array
    {
        $kwargs = [
            'fields' => $fields,
            'domain' => [['sale_ok', '=', true]],
        ];
        if ($limit) {
            $kwargs['limit'] = $limit;
        }

        return $this->execute_kw('product.product', 'search_read', [[]], $kwargs);
    }

    public function getSalesOrder(int $orderId, array $fields = ['id', 'name', 'state', 'partner_id', 'order_line']): ?array
    {
        $result = $this->execute_kw('sale.order', 'read', [[$orderId]], ['fields' => $fields]);
        return $result[0] ?? null;
    }

    public function createSalesOrder(array $orderData): int
    {
        if (!isset($orderData['partner_id'])) {
            throw new \InvalidArgumentException('partner_id is required');
        }

        if (!isset($orderData['order_line']) || empty($orderData['order_line'])) {
            throw new \InvalidArgumentException('order_line is required');
        }

        $orderData['order_line'] = $this->formatOrderLines($orderData['order_line']);
        $orderData = array_merge(config('mapping.defaults.sales_order', []), $orderData);

        return $this->execute_kw('sale.order', 'create', [$orderData]);
    }

    public function confirmSalesOrder(int $orderId): bool
    {
        $this->execute_kw('sale.order', 'action_confirm', [[$orderId]]);
        return true;
    }

    protected function authenticate(): void
    {
        if ($this->uid !== null) {
            return;
        }

        try {
            $response = $this->client->post('jsonrpc', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'call',
                    'params' => [
                        'service' => 'common',
                        'method' => 'authenticate',
                        'args' => [$this->database, $this->username, $this->apiKey, []],
                    ],
                    'id' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception('Authentication failed: ' . $data['error']['message']);
            }

            $this->uid = $data['result'];
        } catch (RequestException $e) {
            throw new \Exception('Authentication failed: ' . $e->getMessage());
        }
    }

    protected function execute_kw(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        $this->authenticate();

        try {
            $response = $this->client->post('jsonrpc', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'call',
                    'params' => [
                        'service' => 'object',
                        'method' => 'execute_kw',
                        'args' => [$this->database, $this->uid, $this->apiKey, $model, $method, $args, $kwargs],
                    ],
                    'id' => uniqid(),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new \Exception($data['error']['message']);
            }

            return $data['result'];
        } catch (RequestException $e) {
            throw new \Exception('API call failed: ' . $e->getMessage());
        }
    }

    protected function formatOrderLines(array $lines): array
    {
        return array_map(fn($line) => [0, 0, $line], $lines);
    }
}
