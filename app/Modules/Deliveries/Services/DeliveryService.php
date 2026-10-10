<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Services;

use CodeIgniter\Database\BaseConnection;

final class DeliveryService
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOrder(int $tenantId, int $preorderId): ?array
    {
        $order = $this->db->table('preorders')
            ->select('preorders.*, sellers.name AS seller_name')
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->where(['preorders.id' => $preorderId, 'preorders.tenant_id' => $tenantId])
            ->get()
            ->getRowArray();

        if ($order === null) {
            return null;
        }

        $order['items'] = $this->db->table('preorder_items')
            ->select('preorder_items.*, products.name AS product_name, products.brand, product_images.id AS image_id')
            ->join('products', 'products.id = preorder_items.product_id')
            ->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')
            ->where(['preorder_items.preorder_id' => $preorderId, 'preorder_items.tenant_id' => $tenantId])
            ->orderBy('preorder_items.id')
            ->get()
            ->getResultArray();

        $order['events'] = $this->db->table('delivery_events')
            ->select('delivery_events.*, preorder_items.id AS item_exists, products.name AS product_name')
            ->join('preorder_items', 'preorder_items.id = delivery_events.preorder_item_id', 'left')
            ->join('products', 'products.id = preorder_items.product_id', 'left')
            ->where(['delivery_events.preorder_id' => $preorderId, 'delivery_events.tenant_id' => $tenantId])
            ->orderBy('delivery_events.id', 'DESC')
            ->get()
            ->getResultArray();

        return $order;
    }

    /**
     * @param array<int, int|string> $quantities
     * @param array{tracking_code?:string, expected_delivery_date?:string, actual_delivery_date?:string, notes?:string} $data
     */
    public function receiveItems(int $tenantId, int $preorderId, int $actorUserId, array $quantities, array $data): void
    {
        $order = $this->findOrder($tenantId, $preorderId);

        if ($order === null) {
            throw new \InvalidArgumentException('Pré-venda não encontrada.');
        }

        $receiptPlan = [];
        foreach ($order['items'] as $item) {
            $quantity = max(0, (int) ($quantities[(int) $item['id']] ?? 0));
            if ($quantity === 0) {
                continue;
            }

            $pending = (int) $item['quantity'] - (int) $item['received_quantity'];
            if ($quantity > $pending) {
                throw new \InvalidArgumentException('A quantidade recebida de ' . $item['product_name'] . ' excede o pendente.');
            }

            $receiptPlan[] = ['item' => $item, 'quantity' => $quantity];
        }

        if ($receiptPlan === []) {
            throw new \InvalidArgumentException('Informe ao menos uma quantidade recebida.');
        }

        $this->db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        $trackingCode = trim((string) ($data['tracking_code'] ?? '')) ?: null;
        $expectedDate = $data['expected_delivery_date'] ?: null;

        foreach ($receiptPlan as $receipt) {
            $item = $receipt['item'];
            $quantity = $receipt['quantity'];
            $newReceivedQuantity = (int) $item['received_quantity'] + $quantity;
            $itemStatus = $newReceivedQuantity === (int) $item['quantity']
                ? DeliveryStatus::RECEIVED
                : DeliveryStatus::PARTIALLY_RECEIVED;

            $this->db->table('preorder_items')
                ->where(['id' => $item['id'], 'tenant_id' => $tenantId])
                ->update([
                    'received_quantity' => $newReceivedQuantity,
                    'status' => $itemStatus,
                    'updated_at' => $now,
                ]);

            $this->recordEvent($tenantId, $preorderId, (int) $item['id'], (string) $item['status'], $itemStatus, $quantity, $trackingCode, $expectedDate, $data['actual_delivery_date'] ?: date('Y-m-d'), $data['notes'] ?? null, $actorUserId, $now);
        }

        $items = $this->db->table('preorder_items')
            ->select('quantity, received_quantity')
            ->where(['preorder_id' => $preorderId, 'tenant_id' => $tenantId])
            ->get()
            ->getResultArray();
        $newStatus = DeliveryStatus::fromItems($items);
        $actualDate = $newStatus === DeliveryStatus::RECEIVED
            ? ($data['actual_delivery_date'] ?: date('Y-m-d'))
            : null;

        $this->db->table('preorders')
            ->where(['id' => $preorderId, 'tenant_id' => $tenantId])
            ->update([
                'status' => $newStatus,
                'tracking_code' => $trackingCode,
                'expected_delivery_date' => $expectedDate,
                'actual_delivery_date' => $actualDate,
                'updated_at' => $now,
            ]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Não foi possível registrar o recebimento.');
        }
    }

    private function recordEvent(
        int $tenantId,
        int $preorderId,
        ?int $itemId,
        ?string $oldStatus,
        string $newStatus,
        int $receivedQuantity,
        ?string $trackingCode,
        ?string $expectedDate,
        ?string $actualDate,
        ?string $notes,
        int $actorUserId,
        string $createdAt,
    ): void {
        $this->db->table('delivery_events')->insert([
            'tenant_id' => $tenantId,
            'preorder_id' => $preorderId,
            'preorder_item_id' => $itemId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'received_quantity' => $receivedQuantity,
            'tracking_code' => $trackingCode,
            'expected_delivery_date' => $expectedDate,
            'actual_delivery_date' => $actualDate,
            'notes' => trim((string) $notes) ?: null,
            'actor_user_id' => $actorUserId,
            'created_at' => $createdAt,
        ]);
    }

}
