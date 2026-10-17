<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Services;

use App\Modules\Deliveries\Repositories\ShipmentRepository;
use CodeIgniter\Database\BaseConnection;

final class ShipmentService
{
    private readonly ShipmentRepository $shipments;

    public function __construct(private readonly BaseConnection $db)
    {
        $this->shipments = new ShipmentRepository($db);
    }

    /** @return list<array<string, mixed>> */
    public function eligibleItems(int $tenantId, int $sellerId): array
    {
        if ($sellerId <= 0) {
            return [];
        }

        return [...$this->eligiblePreorders($tenantId, $sellerId), ...$this->eligibleGarage($tenantId, $sellerId)];
    }

    /**
     * @param array<string, int|string> $quantities
     * @param array<string, mixed> $data
     */
    public function create(int $tenantId, int $sellerId, array $quantities, array $data): int
    {
        $eligible = [];
        foreach ($this->eligibleItems($tenantId, $sellerId) as $item) {
            $eligible[$item['key']] = $item;
        }

        $selection = [];
        foreach ($quantities as $key => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity <= 0 || ! isset($eligible[$key])) {
                continue;
            }
            if ($quantity > (int) $eligible[$key]['available_quantity']) {
                throw new \InvalidArgumentException('A quantidade selecionada excede o disponível para envio.');
            }
            $selection[$key] = $quantity;
        }
        if ($selection === []) {
            throw new \InvalidArgumentException('Selecione ao menos uma miniatura disponível.');
        }

        $this->db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        $shipmentId = $this->shipments->createShipment([
            'tenant_id' => $tenantId,
            'seller_id' => $sellerId,
            'reference' => 'ENV-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
            'carrier' => trim((string) $data['carrier']),
            'tracking_code' => trim((string) ($data['tracking_code'] ?? '')) ?: null,
            'shipping_amount' => number_format($this->money($data['shipping_amount'] ?? 0), 2, '.', ''),
            'status' => ShipmentStatus::ORGANIZED,
            'shipped_at' => $data['shipped_at'] ?: null,
            'expected_delivery_date' => $data['expected_delivery_date'] ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $freight = $this->money($data['shipping_amount'] ?? 0);
        $byValue = ($data['freight_split'] ?? 'quantity') === 'value';
        $weightTotal = 0.0;
        foreach ($selection as $key => $quantity) {
            $item = $eligible[$key];
            $weightTotal += $byValue ? $quantity * (float) $item['unit_price'] : $quantity;
        }
        $remainingFreight = $freight;
        $lastKey = array_key_last($selection);
        foreach ($selection as $key => $quantity) {
            $item = $eligible[$key];
            $weight = $byValue ? $quantity * (float) $item['unit_price'] : $quantity;
            $share = $key === $lastKey ? $remainingFreight : round($freight * $weight / max(1, $weightTotal), 2);
            $remainingFreight -= $share;
            $this->shipments->createShipmentItem([
                'tenant_id' => $tenantId,
                'shipment_id' => $shipmentId,
                'preorder_item_id' => $item['source'] === 'preorder' ? $item['source_id'] : null,
                'garage_item_id' => $item['source'] === 'garage' ? $item['source_id'] : null,
                'quantity' => $quantity,
                'freight_amount' => number_format($share, 2, '.', ''),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->syncSourceStatuses($tenantId, $shipmentId, ShipmentStatus::ORGANIZED, $now);

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Não foi possível organizar o envio.');
        }

        return $shipmentId;
    }

    /** @return list<array<string, mixed>> */
    public function all(int $tenantId): array
    {
        return $this->shipments->all($tenantId);
    }

    /** @return array<string, mixed>|null */
    public function find(int $tenantId, int $shipmentId): ?array
    {
        $shipment = $this->shipments->find($tenantId, $shipmentId);
        if ($shipment === null) {
            return null;
        }

        $rows = $this->shipments->itemsForShipment($tenantId, $shipmentId);

        $shipment['items'] = array_map(static function (array $row): array {
            $isGarage = $row['garage_item_id'] !== null;
            return [
                ...$row,
                'source' => $isGarage ? 'garage' : 'preorder',
                'source_label' => $isGarage ? 'Garagem' : 'Pré-venda',
                'source_url' => $isGarage ? '/garagem/' . $row['garage_id'] : '/pre-vendas/' . $row['preorder_id'],
                'source_reference' => $isGarage ? 'Garagem' : $row['preorder_reference'],
                'product_name' => $isGarage ? $row['garage_name'] : $row['preorder_name'],
                'brand' => $isGarage ? $row['garage_brand'] : $row['preorder_brand'],
                'scale' => $isGarage ? $row['garage_scale'] : $row['preorder_scale'],
                'image_id' => $isGarage ? $row['garage_image_id'] : $row['preorder_image_id'],
            ];
        }, $rows);

        return $shipment;
    }

    /** @param array<string, mixed> $data */
    public function updateStatus(int $tenantId, int $shipmentId, string $status, array $data): void
    {
        $shipment = $this->find($tenantId, $shipmentId);
        if ($shipment === null) {
            throw new \InvalidArgumentException('Envio não encontrado.');
        }
        if (! ShipmentStatus::isValid($status)) {
            throw new \InvalidArgumentException('Status de envio inválido.');
        }

        $updates = [
            'status' => $status,
            'tracking_code' => trim((string) ($data['tracking_code'] ?? '')) ?: null,
            'expected_delivery_date' => $data['expected_delivery_date'] ?: null,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ];
        if ($status === ShipmentStatus::SHIPPED) {
            $updates['shipped_at'] = $data['shipped_at'] ?: date('Y-m-d');
        }
        if ($status === ShipmentStatus::RECEIVED) {
            $updates['shipped_at'] = $data['shipped_at'] ?: ($shipment['shipped_at'] ?: date('Y-m-d'));
            $updates['received_at'] = $data['received_at'] ?: date('Y-m-d');
        }

        $this->shipments->updateShipment($tenantId, $shipmentId, $updates);
        $this->syncSourceStatuses($tenantId, $shipmentId, $status, (string) $updates['updated_at']);
        if ($status === ShipmentStatus::RECEIVED) {
            $this->addToCollection($tenantId, $shipmentId, $updates['received_at']);
        }
    }

    /** @return list<array<string, mixed>> */
    private function eligiblePreorders(int $tenantId, int $sellerId): array
    {
        $items = $this->shipments->eligiblePreorderItems($tenantId, $sellerId, DeliveryStatus::CANCELED);

        $financials = [];
        $eligible = [];
        foreach ($items as $item) {
            $preorderId = (int) $item['preorder_id'];
            $financials[$preorderId] ??= $this->preorderFinancials($tenantId, $preorderId, $item);
            if ($financials[$preorderId]['paid'] + 0.0001 < $financials[$preorderId]['total']) {
                continue;
            }
            $available = (int) $item['received_quantity'] - $this->allocatedQuantity($tenantId, 'preorder_item_id', (int) $item['id']);
            if ($available <= 0) {
                continue;
            }
            $eligible[] = [
                ...$item,
                'key' => 'preorder-' . $item['id'],
                'source' => 'preorder',
                'source_id' => (int) $item['id'],
                'source_label' => 'Pré-venda',
                'source_url' => '/pre-vendas/' . $preorderId,
                'available_quantity' => $available,
            ];
        }
        return $eligible;
    }

    /** @return list<array<string, mixed>> */
    private function eligibleGarage(int $tenantId, int $sellerId): array
    {
        $items = $this->shipments->eligibleGarageItems($tenantId, $sellerId);

        $eligible = [];
        foreach ($items as $item) {
            $total = (int) $item['quantity'] * (float) $item['unit_price'];
            if ((float) $item['paid_amount'] + 0.0001 < $total) {
                continue;
            }
            $available = (int) $item['quantity'] - $this->allocatedQuantity($tenantId, 'garage_item_id', (int) $item['id']);
            if ($available <= 0) {
                continue;
            }
            $eligible[] = [
                ...$item,
                'key' => 'garage-' . $item['id'],
                'source' => 'garage',
                'source_id' => (int) $item['id'],
                'source_label' => 'Garagem',
                'source_url' => '/garagem/' . $item['id'],
                'reference' => 'Garagem',
                'available_quantity' => $available,
            ];
        }
        return $eligible;
    }

    private function allocatedQuantity(int $tenantId, string $column, int $itemId): int
    {
        return $this->shipments->allocatedQuantity($tenantId, $column, $itemId, ShipmentStatus::CANCELED);
    }

    private function syncSourceStatuses(int $tenantId, int $shipmentId, string $shipmentStatus, string $updatedAt): void
    {
        $items = $this->shipments->sourcesForShipment($tenantId, $shipmentId);

        $preorderItemIds = array_values(array_filter(array_column($items, 'preorder_item_id')));
        $garageItemIds = array_values(array_filter(array_column($items, 'garage_item_id')));

        $preorderStatus = match ($shipmentStatus) {
            ShipmentStatus::SHIPPED => DeliveryStatus::IN_TRANSIT,
            ShipmentStatus::RECEIVED => DeliveryStatus::DELIVERED,
            ShipmentStatus::CANCELED => DeliveryStatus::RECEIVED,
            default => DeliveryStatus::AWAITING_SHIPMENT,
        };
        $garageStatus = match ($shipmentStatus) {
            ShipmentStatus::SHIPPED => 'shipping',
            ShipmentStatus::RECEIVED => 'received',
            ShipmentStatus::CANCELED => 'stored',
            default => 'prepared',
        };

        if ($preorderItemIds !== []) {
            $preorderIds = $this->shipments->preorderIdsForItems($tenantId, $preorderItemIds);
            if ($preorderIds !== []) {
                $this->shipments->updatePreorders($tenantId, $preorderIds, ['status' => $preorderStatus, 'updated_at' => $updatedAt]);
            }
        }

        if ($garageItemIds !== []) {
            $this->shipments->updateGarageItems($tenantId, $garageItemIds, ['status' => $garageStatus, 'updated_at' => $updatedAt]);
        }
    }

    private function addToCollection(int $tenantId, int $shipmentId, string $receivedAt): void
    {
        $items = $this->shipments->collectionSources($tenantId, $shipmentId);
        $now = gmdate('Y-m-d H:i:s');
        foreach ($items as $item) {
            if ($this->shipments->collectionItemExists((int) $item['id'])) {
                continue;
            }
            $isGarage = $item['garage_item_id'] !== null;
            $this->shipments->createCollectionItem([
                'tenant_id' => $tenantId,
                'shipment_item_id' => $item['id'],
                'product_id' => $isGarage ? $item['garage_product_id'] : $item['preorder_product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $isGarage ? $item['garage_price'] : $item['preorder_price'],
                'freight_amount' => $item['freight_amount'],
                'received_at' => $receivedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** @param array<string, mixed> $preorder */
    private function preorderFinancials(int $tenantId, int $preorderId, array $preorder): array
    {
        $itemsTotal = $this->shipments->preorderItemsTotal($tenantId, $preorderId);
        $allocated = $this->shipments->allocatedPaymentTotal($tenantId, $preorderId);

        return [
            'total' => $itemsTotal + (float) $preorder['legacy_shipping_amount'] - (float) $preorder['discount_amount'],
            'paid' => (float) $preorder['down_payment_amount'] + $allocated,
        ];
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) str_replace(',', '.', (string) $value)), 2);
    }
}
