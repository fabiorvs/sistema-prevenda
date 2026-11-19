<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use CodeIgniter\Database\ConnectionInterface;

final class InvestmentEntryRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function confirmedPreorderPaymentsForPeriod(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('payments')
            ->select('payments.id, DATE(payments.paid_at) AS paid_date, payments.amount, payments.type, preorders.id AS preorder_id, preorders.reference, sellers.name AS seller_name', false)
            ->join('preorders', 'preorders.id = payments.preorder_id')
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->where(['payments.tenant_id' => $tenantId, 'payments.status' => 'confirmed'])
            ->where('preorders.tenant_id', $tenantId)
            ->where('sellers.tenant_id', $tenantId)
            ->where('payments.paid_at >=', $from . ' 00:00:00')
            ->where('payments.paid_at <=', $to . ' 23:59:59')
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function preorderDownPaymentsForPeriod(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('preorders')
            ->select('preorders.id, preorders.reference, preorders.order_date, preorders.down_payment_amount, sellers.name AS seller_name')
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->where('preorders.tenant_id', $tenantId)
            ->where('sellers.tenant_id', $tenantId)
            ->where('preorders.down_payment_amount >', 0)
            ->where('preorders.order_date >=', $from)
            ->where('preorders.order_date <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function garagePaymentsForPeriod(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('garage_payments')
            ->select('garage_payments.id, DATE(garage_payments.paid_at) AS paid_date, garage_payments.amount, garage_items.id AS garage_item_id, products.brand, products.name AS product_name, sellers.name AS seller_name', false)
            ->join('garage_items', 'garage_items.id = garage_payments.garage_item_id')
            ->join('products', 'products.id = garage_items.product_id')
            ->join('sellers', 'sellers.id = garage_items.seller_id')
            ->where('garage_payments.tenant_id', $tenantId)
            ->where('garage_items.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->where('sellers.tenant_id', $tenantId)
            ->where('garage_payments.paid_at >=', $from . ' 00:00:00')
            ->where('garage_payments.paid_at <=', $to . ' 23:59:59')
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function shipmentsForPeriod(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('shipments')
            ->select('shipments.id, shipments.reference, shipments.shipped_at, shipments.shipping_amount, shipments.carrier, sellers.name AS seller_name')
            ->join('sellers', 'sellers.id = shipments.seller_id')
            ->where('shipments.tenant_id', $tenantId)
            ->where('sellers.tenant_id', $tenantId)
            ->where('shipments.status !=', 'canceled')
            ->where('shipments.shipping_amount >', 0)
            ->where('shipments.shipped_at >=', $from)
            ->where('shipments.shipped_at <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function directPurchasesForPeriod(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('direct_purchases')
            ->select('direct_purchases.id, direct_purchases.purchased_at, direct_purchases.quantity, direct_purchases.unit_price, direct_purchases.store_name, products.name AS product_name, products.brand')
            ->join('products', 'products.id = direct_purchases.product_id')
            ->where(['direct_purchases.tenant_id' => $tenantId, 'products.tenant_id' => $tenantId])
            ->where('direct_purchases.purchased_at >=', $from)
            ->where('direct_purchases.purchased_at <=', $to)
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function importsForPeriod(int $tenantId, string $from, string $to): array
    {
        return $this->db->table('imports')
            ->select('imports.id, imports.purchased_at, imports.item_amount, imports.shipping_amount, imports.tax_amount, imports.exchange_rate, imports.supplier_name, products.name AS product_name, products.brand')
            ->join('products', 'products.id = imports.product_id')
            ->where(['imports.tenant_id' => $tenantId, 'products.tenant_id' => $tenantId])
            ->where('imports.purchased_at >=', $from)
            ->where('imports.purchased_at <=', $to)
            ->get()
            ->getResultArray();
    }
}
