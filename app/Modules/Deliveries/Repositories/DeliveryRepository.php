<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Repositories;

use CodeIgniter\Database\ConnectionInterface;

final class DeliveryRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function findOrder(int $tenantId, int $preorderId): ?array
    {
        return $this->db->table('preorders')->select('preorders.*, sellers.name AS seller_name')->join('sellers', 'sellers.id = preorders.seller_id')->where(['preorders.id' => $preorderId, 'preorders.tenant_id' => $tenantId])->get()->getRowArray();
    }

    /** @return list<array<string, mixed>> */
    public function itemsForOrder(int $tenantId, int $preorderId): array
    {
        return $this->db->table('preorder_items')->select('preorder_items.*, products.name AS product_name, products.brand, product_images.id AS image_id')->join('products', 'products.id = preorder_items.product_id')->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')->where(['preorder_items.preorder_id' => $preorderId, 'preorder_items.tenant_id' => $tenantId])->orderBy('preorder_items.id')->get()->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function eventsForOrder(int $tenantId, int $preorderId): array
    {
        return $this->db->table('delivery_events')->select('delivery_events.*, preorder_items.id AS item_exists, products.name AS product_name')->join('preorder_items', 'preorder_items.id = delivery_events.preorder_item_id', 'left')->join('products', 'products.id = preorder_items.product_id', 'left')->where(['delivery_events.preorder_id' => $preorderId, 'delivery_events.tenant_id' => $tenantId])->orderBy('delivery_events.id', 'DESC')->get()->getResultArray();
    }

    /** @param array<string, mixed> $data */
    public function updateItem(int $tenantId, int $itemId, array $data): void
    {
        $this->db->table('preorder_items')->where(['id' => $itemId, 'tenant_id' => $tenantId])->update($data);
    }

    /** @param array<string, mixed> $data */
    public function createEvent(array $data): void
    {
        $this->db->table('delivery_events')->insert($data);
    }

    /** @return list<array<string, mixed>> */
    public function quantitiesForOrder(int $tenantId, int $preorderId): array
    {
        return $this->db->table('preorder_items')->select('quantity, received_quantity')->where(['preorder_id' => $preorderId, 'tenant_id' => $tenantId])->get()->getResultArray();
    }

    /** @param array<string, mixed> $data */
    public function updateOrder(int $tenantId, int $preorderId, array $data): void
    {
        $this->db->table('preorders')->where(['id' => $preorderId, 'tenant_id' => $tenantId])->update($data);
    }
}
