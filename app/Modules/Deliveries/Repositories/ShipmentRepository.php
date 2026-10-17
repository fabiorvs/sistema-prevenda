<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Repositories;

use CodeIgniter\Database\BaseConnection;

final class ShipmentRepository
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /** @param array<string, mixed> $data */
    public function createShipment(array $data): int
    {
        $this->db->table('shipments')->insert($data);

        return (int) $this->db->insertID();
    }

    /** @param array<string, mixed> $data */
    public function createShipmentItem(array $data): void
    {
        $this->db->table('shipment_items')->insert($data);
    }

    /** @return list<array<string, mixed>> */
    public function all(int $tenantId): array
    {
        return $this->db->table('shipments')->select('shipments.*, sellers.name AS seller_name, COALESCE(SUM(shipment_items.quantity), 0) AS total_quantity', false)->join('sellers', 'sellers.id = shipments.seller_id')->join('shipment_items', 'shipment_items.shipment_id = shipments.id', 'left')->where('shipments.tenant_id', $tenantId)->groupBy('shipments.id')->orderBy('shipments.created_at', 'DESC')->get()->getResultArray();
    }

    /** @return array<string, mixed>|null */
    public function find(int $tenantId, int $shipmentId): ?array
    {
        return $this->db->table('shipments')->select('shipments.*, sellers.name AS seller_name')->join('sellers', 'sellers.id = shipments.seller_id')->where(['shipments.id' => $shipmentId, 'shipments.tenant_id' => $tenantId])->get()->getRowArray();
    }

    /** @return list<array<string, mixed>> */
    public function itemsForShipment(int $tenantId, int $shipmentId): array
    {
        return $this->db->table('shipment_items')->select('shipment_items.*, preorder_items.preorder_id, preorders.reference AS preorder_reference, garage_items.id AS garage_id, products_pre.name AS preorder_name, products_pre.brand AS preorder_brand, products_pre.scale AS preorder_scale, product_images_pre.id AS preorder_image_id, products_garage.name AS garage_name, products_garage.brand AS garage_brand, products_garage.scale AS garage_scale, product_images_garage.id AS garage_image_id')->join('preorder_items', 'preorder_items.id = shipment_items.preorder_item_id', 'left')->join('preorders', 'preorders.id = preorder_items.preorder_id', 'left')->join('products AS products_pre', 'products_pre.id = preorder_items.product_id', 'left')->join('product_images AS product_images_pre', 'product_images_pre.product_id = products_pre.id AND product_images_pre.is_primary = 1', 'left')->join('garage_items', 'garage_items.id = shipment_items.garage_item_id', 'left')->join('products AS products_garage', 'products_garage.id = garage_items.product_id', 'left')->join('product_images AS product_images_garage', 'product_images_garage.product_id = products_garage.id AND product_images_garage.is_primary = 1', 'left')->where(['shipment_items.shipment_id' => $shipmentId, 'shipment_items.tenant_id' => $tenantId])->orderBy('shipment_items.id')->get()->getResultArray();
    }

    /** @param array<string, mixed> $data */
    public function updateShipment(int $tenantId, int $shipmentId, array $data): void
    {
        $this->db->table('shipments')->where(['id' => $shipmentId, 'tenant_id' => $tenantId])->update($data);
    }

    /** @return list<array<string, mixed>> */
    public function eligiblePreorderItems(int $tenantId, int $sellerId, string $canceledStatus): array
    {
        return $this->db->table('preorder_items')->select('preorder_items.*, preorders.reference, preorders.down_payment_amount, preorders.shipping_amount AS legacy_shipping_amount, preorders.discount_amount, products.name AS product_name, products.brand, products.scale, product_images.id AS image_id')->join('preorders', 'preorders.id = preorder_items.preorder_id')->join('products', 'products.id = preorder_items.product_id')->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')->where(['preorder_items.tenant_id' => $tenantId, 'preorders.tenant_id' => $tenantId, 'preorders.seller_id' => $sellerId])->where('preorder_items.received_quantity >', 0)->where('preorders.status !=', $canceledStatus)->orderBy('preorders.order_date')->get()->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function eligibleGarageItems(int $tenantId, int $sellerId): array
    {
        return $this->db->table('garage_items')->select('garage_items.*, products.name AS product_name, products.brand, products.scale, MAX(product_images.id) AS image_id, COALESCE(SUM(garage_payments.amount), 0) AS paid_amount', false)->join('products', 'products.id = garage_items.product_id')->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')->join('garage_payments', 'garage_payments.garage_item_id = garage_items.id', 'left')->where(['garage_items.tenant_id' => $tenantId, 'garage_items.seller_id' => $sellerId, 'garage_items.status' => 'stored'])->groupBy('garage_items.id')->orderBy('garage_items.acquired_at')->get()->getResultArray();
    }

    public function allocatedQuantity(int $tenantId, string $sourceColumn, int $itemId, string $canceledStatus): int
    {
        if (! in_array($sourceColumn, ['preorder_item_id', 'garage_item_id'], true)) {
            throw new \InvalidArgumentException('Origem de envio inválida.');
        }

        $row = $this->db->table('shipment_items')->select('COALESCE(SUM(shipment_items.quantity), 0) AS quantity', false)->join('shipments', 'shipments.id = shipment_items.shipment_id')->where(['shipment_items.tenant_id' => $tenantId, 'shipment_items.' . $sourceColumn => $itemId])->where('shipments.status !=', $canceledStatus)->get()->getRowArray();

        return (int) ($row['quantity'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function sourcesForShipment(int $tenantId, int $shipmentId): array
    {
        return $this->db->table('shipment_items')->select('preorder_item_id, garage_item_id')->where(['tenant_id' => $tenantId, 'shipment_id' => $shipmentId])->get()->getResultArray();
    }

    /** @param list<int> $itemIds @return list<int> */
    public function preorderIdsForItems(int $tenantId, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $rows = $this->db->table('preorder_items')->select('preorder_id')->where('tenant_id', $tenantId)->whereIn('id', $itemIds)->get()->getResultArray();

        return array_values(array_unique(array_map(static fn (array $row): int => (int) $row['preorder_id'], $rows)));
    }

    /** @param list<int> $preorderIds @param array<string, mixed> $data */
    public function updatePreorders(int $tenantId, array $preorderIds, array $data): void
    {
        if ($preorderIds !== []) {
            $this->db->table('preorders')->where('tenant_id', $tenantId)->whereIn('id', $preorderIds)->update($data);
        }
    }

    /** @param list<int> $garageItemIds @param array<string, mixed> $data */
    public function updateGarageItems(int $tenantId, array $garageItemIds, array $data): void
    {
        if ($garageItemIds !== []) {
            $this->db->table('garage_items')->where('tenant_id', $tenantId)->whereIn('id', $garageItemIds)->update($data);
        }
    }

    /** @return list<array<string, mixed>> */
    public function collectionSources(int $tenantId, int $shipmentId): array
    {
        return $this->db->table('shipment_items')->select('shipment_items.*, preorder_items.product_id AS preorder_product_id, preorder_items.unit_price AS preorder_price, garage_items.product_id AS garage_product_id, garage_items.unit_price AS garage_price')->join('preorder_items', 'preorder_items.id = shipment_items.preorder_item_id', 'left')->join('garage_items', 'garage_items.id = shipment_items.garage_item_id', 'left')->where(['shipment_items.tenant_id' => $tenantId, 'shipment_items.shipment_id' => $shipmentId])->get()->getResultArray();
    }

    public function collectionItemExists(int $shipmentItemId): bool
    {
        return $this->db->table('collection_items')->where('shipment_item_id', $shipmentItemId)->countAllResults() > 0;
    }

    /** @param array<string, mixed> $data */
    public function createCollectionItem(array $data): void
    {
        $this->db->table('collection_items')->insert($data);
    }

    public function preorderItemsTotal(int $tenantId, int $preorderId): float
    {
        $row = $this->db->table('preorder_items')->select('COALESCE(SUM(quantity * unit_price), 0) AS total', false)->where(['tenant_id' => $tenantId, 'preorder_id' => $preorderId])->get()->getRowArray();

        return (float) ($row['total'] ?? 0);
    }

    public function allocatedPaymentTotal(int $tenantId, int $preorderId): float
    {
        $row = $this->db->table('financial_entries')->select('COALESCE(SUM(payment_allocations.amount), 0) AS total', false)->join('payment_allocations', 'payment_allocations.financial_entry_id = financial_entries.id', 'left')->where(['financial_entries.tenant_id' => $tenantId, 'financial_entries.preorder_id' => $preorderId])->get()->getRowArray();

        return (float) ($row['total'] ?? 0);
    }
}
