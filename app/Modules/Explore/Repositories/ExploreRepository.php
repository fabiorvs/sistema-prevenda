<?php

declare(strict_types=1);

namespace App\Modules\Explore\Repositories;

use CodeIgniter\Database\BaseConnection;

final class ExploreRepository
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function searchProducts(int $tenantId, string $query): array
    {
        return $this->db->table('products')
            ->select('id, name, brand')
            ->where('tenant_id', $tenantId)
            ->groupStart()
            ->like('name', $query)
            ->orLike('brand', $query)
            ->groupEnd()
            ->limit(12)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function searchSellers(int $tenantId, string $query): array
    {
        return $this->db->table('sellers')
            ->select('id, name')
            ->where('tenant_id', $tenantId)
            ->like('name', $query)
            ->limit(12)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function searchPreorders(int $tenantId, string $query): array
    {
        return $this->db->table('preorders')
            ->select('id, reference')
            ->where('tenant_id', $tenantId)
            ->like('reference', $query)
            ->limit(12)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function searchGarageItems(int $tenantId, string $query): array
    {
        return $this->db->table('garage_items')
            ->select('garage_items.id, products.name, products.brand')
            ->join('products', 'products.id = garage_items.product_id')
            ->where(['garage_items.tenant_id' => $tenantId, 'products.tenant_id' => $tenantId])
            ->groupStart()
            ->like('products.name', $query)
            ->orLike('products.brand', $query)
            ->groupEnd()
            ->limit(12)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function searchPurchases(int $tenantId, string $query): array
    {
        return $this->db->table('direct_purchases')
            ->select('direct_purchases.id, direct_purchases.store_name, products.name')
            ->join('products', 'products.id = direct_purchases.product_id')
            ->where(['direct_purchases.tenant_id' => $tenantId, 'products.tenant_id' => $tenantId])
            ->groupStart()
            ->like('products.name', $query)
            ->orLike('direct_purchases.store_name', $query)
            ->groupEnd()
            ->limit(12)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function calendarEvents(int $tenantId, string $from, string $to): array
    {
        return [
            ...$this->financialEntryEvents($tenantId, $from, $to),
            ...$this->shipmentEvents($tenantId, $from, $to),
            ...$this->preorderArrivalEvents($tenantId, $from, $to),
            ...$this->importArrivalEvents($tenantId, $from, $to),
            ...$this->directPurchaseEvents($tenantId, $from, $to),
            ...$this->garagePurchaseEvents($tenantId, $from, $to),
            ...$this->importPurchaseEvents($tenantId, $from, $to),
            ...$this->reservationEvents($tenantId, $from, $to),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function financialEntryEvents(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('financial_entries')
            ->select("financial_entries.due_date AS date, financial_entries.description AS title, 'Pagamento' AS type, financial_entries.id, 'financial_entry' AS source", false)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date >=', $from)
            ->where('due_date <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    private function shipmentEvents(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('shipments')
            ->select("expected_delivery_date AS date, reference AS title, 'Entrega' AS type, id, 'shipment' AS source", false)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['organized', 'shipped'])
            ->where('expected_delivery_date >=', $from)
            ->where('expected_delivery_date <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    private function preorderArrivalEvents(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('preorders')
            ->select("expected_delivery_date AS date, reference AS title, 'Chegada ao vendedor' AS type, id, 'preorder' AS source", false)
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['received', 'delivered', 'canceled'])
            ->where('expected_delivery_date >=', $from)
            ->where('expected_delivery_date <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    private function importArrivalEvents(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('imports')
            ->select("imports.expected_at AS date, CONCAT(products.brand, ' — ', products.name) AS title, 'Importação' AS type, imports.id, 'import' AS source", false)
            ->join('products', 'products.id = imports.product_id')
            ->where(['imports.tenant_id' => $tenantId, 'products.tenant_id' => $tenantId])
            ->whereNotIn('imports.status', ['received'])
            ->where('imports.expected_at >=', $from)
            ->where('imports.expected_at <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    private function directPurchaseEvents(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('direct_purchases')
            ->select("direct_purchases.purchased_at AS date, CONCAT(products.brand, ' — ', products.name, ' · ', direct_purchases.store_name) AS title, 'Compra' AS type, direct_purchases.id, 'purchase' AS source", false)
            ->join('products', 'products.id = direct_purchases.product_id')
            ->where(['direct_purchases.tenant_id' => $tenantId, 'products.tenant_id' => $tenantId])
            ->where('direct_purchases.purchased_at >=', $from)
            ->where('direct_purchases.purchased_at <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    private function garagePurchaseEvents(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('garage_items')
            ->select("garage_items.acquired_at AS date, CONCAT('Garagem · ', products.brand, ' — ', products.name, ' · ', sellers.name) AS title, 'Compra' AS type, garage_items.id, 'garage' AS source", false)
            ->join('products', 'products.id = garage_items.product_id')
            ->join('sellers', 'sellers.id = garage_items.seller_id')
            ->where([
                'garage_items.tenant_id' => $tenantId,
                'products.tenant_id' => $tenantId,
                'sellers.tenant_id' => $tenantId,
            ])
            ->where('garage_items.status !=', 'canceled')
            ->where('garage_items.acquired_at >=', $from)
            ->where('garage_items.acquired_at <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    private function importPurchaseEvents(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('imports')
            ->select("imports.purchased_at AS date, CONCAT('Importação · ', products.brand, ' — ', products.name, ' · ', imports.supplier_name) AS title, 'Compra' AS type, imports.id, 'import' AS source", false)
            ->join('products', 'products.id = imports.product_id')
            ->where(['imports.tenant_id' => $tenantId, 'products.tenant_id' => $tenantId])
            ->where('imports.purchased_at >=', $from)
            ->where('imports.purchased_at <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    private function reservationEvents(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('preorders')
            ->select("preorders.order_date AS date, CONCAT(preorders.reference, ' · ', sellers.name) AS title, 'Reserva' AS type, preorders.id, 'preorder' AS source", false)
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->where(['preorders.tenant_id' => $tenantId, 'sellers.tenant_id' => $tenantId])
            ->where('preorders.status !=', 'canceled')
            ->where('preorders.order_date >=', $from)
            ->where('preorders.order_date <=', $to)
            ->get()
            ->getResultArray();
    }
}
