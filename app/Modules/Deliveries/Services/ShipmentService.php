<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Services;

use CodeIgniter\Database\BaseConnection;

final class ShipmentService
{
    public function __construct(private readonly BaseConnection $db)
    {
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
        $this->db->table('shipments')->insert([
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
        $shipmentId = (int) $this->db->insertID();

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
            $this->db->table('shipment_items')->insert([
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
        return $this->db->table('shipments')
            ->select('shipments.*, sellers.name AS seller_name, COALESCE(SUM(shipment_items.quantity), 0) AS total_quantity', false)
            ->join('sellers', 'sellers.id = shipments.seller_id')
            ->join('shipment_items', 'shipment_items.shipment_id = shipments.id', 'left')
            ->where('shipments.tenant_id', $tenantId)
            ->groupBy('shipments.id')
            ->orderBy('shipments.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /** @return array<string, mixed>|null */
    public function find(int $tenantId, int $shipmentId): ?array
    {
        $shipment = $this->db->table('shipments')
            ->select('shipments.*, sellers.name AS seller_name')
            ->join('sellers', 'sellers.id = shipments.seller_id')
            ->where(['shipments.id' => $shipmentId, 'shipments.tenant_id' => $tenantId])
            ->get()
            ->getRowArray();
        if ($shipment === null) {
            return null;
        }

        $rows = $this->db->table('shipment_items')
            ->select('shipment_items.*, preorder_items.preorder_id, preorders.reference AS preorder_reference, garage_items.id AS garage_id, products_pre.name AS preorder_name, products_pre.brand AS preorder_brand, products_pre.scale AS preorder_scale, product_images_pre.id AS preorder_image_id, products_garage.name AS garage_name, products_garage.brand AS garage_brand, products_garage.scale AS garage_scale, product_images_garage.id AS garage_image_id')
            ->join('preorder_items', 'preorder_items.id = shipment_items.preorder_item_id', 'left')
            ->join('preorders', 'preorders.id = preorder_items.preorder_id', 'left')
            ->join('products AS products_pre', 'products_pre.id = preorder_items.product_id', 'left')
            ->join('product_images AS product_images_pre', 'product_images_pre.product_id = products_pre.id AND product_images_pre.is_primary = 1', 'left')
            ->join('garage_items', 'garage_items.id = shipment_items.garage_item_id', 'left')
            ->join('products AS products_garage', 'products_garage.id = garage_items.product_id', 'left')
            ->join('product_images AS product_images_garage', 'product_images_garage.product_id = products_garage.id AND product_images_garage.is_primary = 1', 'left')
            ->where(['shipment_items.shipment_id' => $shipmentId, 'shipment_items.tenant_id' => $tenantId])
            ->orderBy('shipment_items.id')
            ->get()
            ->getResultArray();

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

        $this->db->table('shipments')->where(['id' => $shipmentId, 'tenant_id' => $tenantId])->update($updates);
        $this->syncSourceStatuses($tenantId, $shipmentId, $status, (string) $updates['updated_at']);
        if ($status === ShipmentStatus::RECEIVED) {
            $this->addToCollection($tenantId, $shipmentId, $updates['received_at']);
        }
    }

    /** @return list<array<string, mixed>> */
    private function eligiblePreorders(int $tenantId, int $sellerId): array
    {
        $items = $this->db->table('preorder_items')
            ->select('preorder_items.*, preorders.reference, preorders.down_payment_amount, preorders.shipping_amount AS legacy_shipping_amount, preorders.discount_amount, products.name AS product_name, products.brand, products.scale, product_images.id AS image_id')
            ->join('preorders', 'preorders.id = preorder_items.preorder_id')
            ->join('products', 'products.id = preorder_items.product_id')
            ->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')
            ->where(['preorder_items.tenant_id' => $tenantId, 'preorders.tenant_id' => $tenantId, 'preorders.seller_id' => $sellerId])
            ->where('preorder_items.received_quantity >', 0)
            ->where('preorders.status !=', DeliveryStatus::CANCELED)
            ->orderBy('preorders.order_date')
            ->get()
            ->getResultArray();

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
        $items = $this->db->table('garage_items')
            ->select('garage_items.*, products.name AS product_name, products.brand, products.scale, MAX(product_images.id) AS image_id, COALESCE(SUM(garage_payments.amount), 0) AS paid_amount', false)
            ->join('products', 'products.id = garage_items.product_id')
            ->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')
            ->join('garage_payments', 'garage_payments.garage_item_id = garage_items.id', 'left')
            ->where(['garage_items.tenant_id' => $tenantId, 'garage_items.seller_id' => $sellerId, 'garage_items.status' => 'stored'])
            ->groupBy('garage_items.id')
            ->orderBy('garage_items.acquired_at')
            ->get()
            ->getResultArray();

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
        return (int) ($this->db->table('shipment_items')
            ->select('COALESCE(SUM(shipment_items.quantity), 0) AS quantity', false)
            ->join('shipments', 'shipments.id = shipment_items.shipment_id')
            ->where(['shipment_items.tenant_id' => $tenantId, 'shipment_items.' . $column => $itemId])
            ->where('shipments.status !=', ShipmentStatus::CANCELED)
            ->get()
            ->getRowArray()['quantity'] ?? 0);
    }

    private function syncSourceStatuses(int $tenantId, int $shipmentId, string $shipmentStatus, string $updatedAt): void
    {
        $items = $this->db->table('shipment_items')
            ->select('preorder_item_id, garage_item_id')
            ->where(['tenant_id' => $tenantId, 'shipment_id' => $shipmentId])
            ->get()
            ->getResultArray();

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
            $preorderIds = $this->db->table('preorder_items')
                ->select('preorder_id')
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $preorderItemIds)
                ->get()
                ->getResultArray();
            $preorderIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['preorder_id'], $preorderIds)));
            if ($preorderIds !== []) {
                $this->db->table('preorders')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('id', $preorderIds)
                    ->update(['status' => $preorderStatus, 'updated_at' => $updatedAt]);
            }
        }

        if ($garageItemIds !== []) {
            $this->db->table('garage_items')
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $garageItemIds)
                ->update(['status' => $garageStatus, 'updated_at' => $updatedAt]);
        }
    }

    private function addToCollection(int $tenantId, int $shipmentId, string $receivedAt): void
    {
        $items = $this->db->table('shipment_items')
            ->select('shipment_items.*, preorder_items.product_id AS preorder_product_id, preorder_items.unit_price AS preorder_price, garage_items.product_id AS garage_product_id, garage_items.unit_price AS garage_price')
            ->join('preorder_items', 'preorder_items.id = shipment_items.preorder_item_id', 'left')
            ->join('garage_items', 'garage_items.id = shipment_items.garage_item_id', 'left')
            ->where(['shipment_items.tenant_id' => $tenantId, 'shipment_items.shipment_id' => $shipmentId])
            ->get()->getResultArray();
        $now = gmdate('Y-m-d H:i:s');
        foreach ($items as $item) {
            $exists = $this->db->table('collection_items')->where('shipment_item_id', $item['id'])->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $isGarage = $item['garage_item_id'] !== null;
            $this->db->table('collection_items')->insert([
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
        $itemsTotal = (float) ($this->db->table('preorder_items')->select('COALESCE(SUM(quantity * unit_price), 0) AS total', false)->where(['tenant_id' => $tenantId, 'preorder_id' => $preorderId])->get()->getRowArray()['total'] ?? 0);
        $allocated = (float) ($this->db->table('financial_entries')->select('COALESCE(SUM(payment_allocations.amount), 0) AS total', false)->join('payment_allocations', 'payment_allocations.financial_entry_id = financial_entries.id', 'left')->where(['financial_entries.tenant_id' => $tenantId, 'financial_entries.preorder_id' => $preorderId])->get()->getRowArray()['total'] ?? 0);

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
