<?php

namespace App\Services;

class ERPMapper
{
    protected array $mapping;

    public function __construct()
    {
        $this->mapping = config('mapping');
    }

    public function mapSalesOrder(array $payload): array
    {
        $mapping = $this->mapping['sales_order'] ?? [];
        $lineMapping = $mapping['lines'] ?? [];

        $mainMapping = $mapping;
        unset($mainMapping['lines']);

        $mappedOrder = $this->mapFields($payload, $mainMapping);

        if (isset($payload['items']) && is_array($payload['items'])) {
            $mappedOrder['order_line'] = array_map(
                fn($item) => $this->mapFields($item, $lineMapping),
                $payload['items']
            );
        }

        return $mappedOrder;
    }

    public function mapCustomer(array $payload): array
    {
        return $this->mapFields($payload, $this->mapping['customer'] ?? []);
    }

    public function mapProduct(array $payload): array
    {
        return $this->mapFields($payload, $this->mapping['product'] ?? []);
    }

    public function reverseMapSalesOrder(array $odooData): array
    {
        $mapping = $this->mapping['sales_order'] ?? [];
        $lineMapping = $mapping['lines'] ?? [];

        $reversedOrder = $this->reverseMapFields($odooData, $mapping);
        unset($reversedOrder['lines']);

        if (isset($odooData['order_line']) && is_array($odooData['order_line'])) {
            $reversedOrder['items'] = array_map(
                fn($line) => $this->reverseMapFields(
                    is_array($line) && isset($line[2]) ? $line[2] : (is_array($line) ? $line : []),
                    $lineMapping
                ),
                $odooData['order_line']
            );
        }

        return $reversedOrder;
    }

    protected function mapFields(array $data, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $externalField => $odooField) {
            if (isset($data[$externalField])) {
                $mapped[$odooField] = $data[$externalField];
            }
        }
        return $mapped;
    }

    protected function reverseMapFields(array $data, array $mapping): array
    {
        $reversed = [];
        foreach ($mapping as $externalField => $odooField) {
            if (is_array($odooField)) {
                continue;
            }
            if (isset($data[$odooField])) {
                $reversed[$externalField] = $data[$odooField];
            }
        }
        return $reversed;
    }
}
