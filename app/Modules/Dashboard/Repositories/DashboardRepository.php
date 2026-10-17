<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Repositories;

use CodeIgniter\Database\ConnectionInterface;

final class DashboardRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function orders(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        $query = $this->db->table('preorders')->select("preorders.*, sellers.name AS seller_name, COALESCE(SUM(preorder_items.quantity * preorder_items.unit_price), 0) AS items_total, GROUP_CONCAT(DISTINCT CONCAT(products.brand, ' — ', products.name) ORDER BY products.brand, products.name SEPARATOR ' | ') AS product_names", false)->join('sellers', 'sellers.id = preorders.seller_id')->join('preorder_items', 'preorder_items.preorder_id = preorders.id', 'left')->join('products', 'products.id = preorder_items.product_id', 'left')->where('preorders.tenant_id', $tenantId)->groupBy('preorders.id');

        if ($sellerId) {
            $query->where('preorders.seller_id', $sellerId);
        }
        if ($from) {
            $query->where('preorders.order_date >=', $from);
        }
        if ($to) {
            $query->where('preorders.order_date <=', $to);
        }

        return $query->get()->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function payments(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        $query = $this->db->table('payments')->select('payments.preorder_id, payments.amount, payments.type')->join('preorders', 'preorders.id = payments.preorder_id')->where(['payments.tenant_id' => $tenantId, 'payments.status' => 'confirmed']);

        if ($sellerId) {
            $query->where('preorders.seller_id', $sellerId);
        }
        if ($from) {
            $query->where('preorders.order_date >=', $from);
        }
        if ($to) {
            $query->where('preorders.order_date <=', $to);
        }

        return $query->get()->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function garageItems(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        $query = $this->db->table('garage_items')->select('garage_items.*, COALESCE(SUM(garage_payments.amount), 0) AS paid', false)->join('garage_payments', 'garage_payments.garage_item_id = garage_items.id', 'left')->where('garage_items.tenant_id', $tenantId)->where('garage_items.status !=', 'canceled')->groupBy('garage_items.id');

        if ($sellerId) {
            $query->where('garage_items.seller_id', $sellerId);
        }
        if ($from) {
            $query->where('garage_items.acquired_at >=', $from);
        }
        if ($to) {
            $query->where('garage_items.acquired_at <=', $to);
        }

        return $query->get()->getResultArray();
    }

    /** @return array{items:mixed,invested:mixed,imports:int} */
    public function collectionSummary(int $tenantId): array
    {
        $collection = $this->db->table('collection_items')->select('COALESCE(SUM(quantity), 0) AS items, COALESCE(SUM(quantity * unit_cost + freight_amount), 0) AS invested', false)->where('tenant_id', $tenantId)->get()->getRowArray() ?? ['items' => 0, 'invested' => 0];

        return [
            'items' => $collection['items'],
            'invested' => $collection['invested'],
            'imports' => $this->db->table('imports')->where('tenant_id', $tenantId)->where('status !=', 'received')->countAllResults(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function upcomingPreorderDue(int $tenantId, ?int $sellerId): array
    {
        $query = $this->db->table('financial_entries')->select("financial_entries.*, preorders.reference, sellers.name AS seller_name, (SELECT GROUP_CONCAT(DISTINCT CONCAT(products.brand, ' — ', products.name) ORDER BY products.brand, products.name SEPARATOR ' | ') FROM preorder_items JOIN products ON products.id = preorder_items.product_id WHERE preorder_items.preorder_id = preorders.id) AS product_names", false)->join('preorders', 'preorders.id = financial_entries.preorder_id')->join('sellers', 'sellers.id = preorders.seller_id')->where('financial_entries.tenant_id', $tenantId)->whereIn('financial_entries.status', ['pending', 'partial'])->orderBy('financial_entries.due_date', 'ASC')->limit(5);

        if ($sellerId) {
            $query->where('preorders.seller_id', $sellerId);
        }

        return $query->get()->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function upcomingGarageDue(int $tenantId, ?int $sellerId): array
    {
        $query = $this->db->table('garage_items')->select('garage_items.next_payment_date AS due_date, sellers.name AS seller_name, products.name AS product_name, products.brand, garage_items.quantity, garage_items.unit_price, COALESCE(SUM(garage_payments.amount), 0) AS paid_amount', false)->join('sellers', 'sellers.id = garage_items.seller_id')->join('products', 'products.id = garage_items.product_id')->join('garage_payments', 'garage_payments.garage_item_id = garage_items.id', 'left')->where('garage_items.tenant_id', $tenantId)->where('garage_items.status !=', 'canceled')->where('garage_items.next_payment_date IS NOT NULL', null, false)->groupBy('garage_items.id');

        if ($sellerId) {
            $query->where('garage_items.seller_id', $sellerId);
        }

        return $query->get()->getResultArray();
    }

    /** @return array{overdue:int,late_shipments:int,waiting:int} */
    public function alertCounts(int $tenantId, string $today, string $waitingBefore): array
    {
        return [
            'overdue' => $this->db->table('financial_entries')->where('tenant_id', $tenantId)->whereIn('status', ['pending', 'partial'])->where('due_date <', $today)->countAllResults(),
            'late_shipments' => $this->db->table('shipments')->where('tenant_id', $tenantId)->where('status', 'shipped')->where('expected_delivery_date <', $today)->countAllResults(),
            'waiting' => $this->db->table('preorders')->where('tenant_id', $tenantId)->where('status', 'awaiting_shipment')->where('updated_at <', $waitingBefore)->countAllResults(),
        ];
    }
}
