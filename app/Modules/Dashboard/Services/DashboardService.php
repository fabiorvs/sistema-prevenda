<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use CodeIgniter\Database\ConnectionInterface;

final class DashboardService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** @return array<string, mixed> */
    public function report(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        $orders = $this->orders($tenantId, $sellerId, $from, $to);
        $payments = $this->payments($tenantId, $sellerId, $from, $to);
        $paidByOrder = [];

        foreach ($payments as $payment) {
            $sign = $payment['type'] === 'refund' ? -1 : 1;
            $paidByOrder[(int) $payment['preorder_id']] = ($paidByOrder[(int) $payment['preorder_id']] ?? 0) + ($sign * (float) $payment['amount']);
        }

        $total = 0.0;
        $paid = 0.0;
        $sellerSummary = [];
        foreach ($orders as &$order) {
            $order['total'] = (float) $order['items_total'] + (float) $order['shipping_amount'] - (float) $order['discount_amount'];
            $order['paid'] = (float) $order['down_payment_amount'] + ($paidByOrder[(int) $order['id']] ?? 0.0);
            $order['balance'] = max(0, $order['total'] - $order['paid']);
            $total += $order['total'];
            $paid += $order['paid'];

            $key = (int) $order['seller_id'];
            if (! isset($sellerSummary[$key])) {
                $sellerSummary[$key] = ['name' => $order['seller_name'], 'total' => 0.0, 'balance' => 0.0, 'orders' => 0];
            }
            $sellerSummary[$key]['total'] += $order['total'];
            $sellerSummary[$key]['balance'] += $order['balance'];
            $sellerSummary[$key]['orders']++;
        }
        unset($order);

        return [
            'cards' => ['total' => $total, 'paid' => $paid, 'balance' => max(0, $total - $paid), 'pending' => count(array_filter($orders, static fn (array $order): bool => $order['balance'] > 0))],
            'orders' => $orders,
            'seller_summary' => array_values($sellerSummary),
            'monthly' => $this->monthly($orders),
            'upcoming_deliveries' => $this->upcomingDeliveries($orders),
            'upcoming_due' => $this->upcomingDue($tenantId, $sellerId),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function orders(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        $query = $this->db->table('preorders')
            ->select('preorders.*, sellers.name AS seller_name, COALESCE(SUM(preorder_items.quantity * preorder_items.unit_price), 0) AS items_total', false)
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->join('preorder_items', 'preorder_items.preorder_id = preorders.id', 'left')
            ->where('preorders.tenant_id', $tenantId)
            ->groupBy('preorders.id');

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
    private function payments(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        $query = $this->db->table('payments')
            ->select('payments.preorder_id, payments.amount, payments.type')
            ->join('preorders', 'preorders.id = payments.preorder_id')
            ->where(['payments.tenant_id' => $tenantId, 'payments.status' => 'confirmed']);

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

    /** @param list<array<string, mixed>> $orders @return array{labels:list<string>, total:list<float>, paid:list<float>} */
    private function monthly(array $orders): array
    {
        $months = [];
        foreach ($orders as $order) {
            $date = $order['expected_delivery_date'] ?: $order['order_date'];
            $key = date('Y-m', strtotime((string) $date));
            $months[$key] ??= ['total' => 0.0, 'paid' => 0.0];
            $months[$key]['total'] += (float) $order['total'];
            $months[$key]['paid'] += (float) $order['paid'];
        }
        ksort($months);
        return ['labels' => array_map(static fn (string $month): string => date('m/Y', strtotime($month . '-01')), array_keys($months)), 'total' => array_column($months, 'total'), 'paid' => array_column($months, 'paid')];
    }

    /** @param list<array<string, mixed>> $orders @return list<array<string, mixed>> */
    private function upcomingDeliveries(array $orders): array
    {
        $orders = array_filter($orders, static fn (array $order): bool => ! empty($order['expected_delivery_date']) && $order['actual_delivery_date'] === null);
        usort($orders, static fn (array $left, array $right): int => strcmp((string) $left['expected_delivery_date'], (string) $right['expected_delivery_date']));
        return array_slice($orders, 0, 5);
    }

    /** @return list<array<string, mixed>> */
    private function upcomingDue(int $tenantId, ?int $sellerId): array
    {
        $query = $this->db->table('financial_entries')
            ->select('financial_entries.*, preorders.reference, sellers.name AS seller_name')
            ->join('preorders', 'preorders.id = financial_entries.preorder_id')
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->where('financial_entries.tenant_id', $tenantId)
            ->whereIn('financial_entries.status', ['pending', 'partial'])
            ->orderBy('financial_entries.due_date', 'ASC')
            ->limit(5);
        if ($sellerId) {
            $query->where('preorders.seller_id', $sellerId);
        }
        return $query->get()->getResultArray();
    }
}
