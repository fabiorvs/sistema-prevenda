<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Dashboard\Repositories\DashboardRepository;
use CodeIgniter\Database\ConnectionInterface;

final class DashboardService
{
    private readonly DashboardRepository $dashboard;

    public function __construct(private readonly ConnectionInterface $db)
    {
        $this->dashboard = new DashboardRepository($db);
    }

    /** @return array<string, mixed> */
    public function report(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        $orders = $this->orders($tenantId, $sellerId, $from, $to);
        $payments = $this->payments($tenantId, $sellerId, $from, $to);
        $garageItems = $this->garageItems($tenantId, $sellerId, $from, $to);
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
            'monthly' => $this->monthly($orders, $garageItems),
            'status_summary' => $this->statusSummary($orders),
            'collection_summary' => $this->collectionSummary($tenantId),
            'upcoming_deliveries' => $this->upcomingDeliveries($orders),
            'upcoming_due' => $this->upcomingDue($tenantId, $sellerId),
            'alerts' => $this->alerts($tenantId),
        ];
    }

    /** @param list<array<string, mixed>> $orders @return array{labels:list<string>, values:list<int>, colors:list<string>} */
    private function statusSummary(array $orders): array
    {
        $groups = [
            'Aguardando lançamento' => ['awaiting_release', 'reserved', 'open'],
            'No vendedor' => ['received', 'partially_received'],
            'Envio preparado' => ['awaiting_shipment'],
            'Em transporte' => ['in_transit'],
            'Recebidas' => ['delivered'],
            'Canceladas' => ['canceled'],
        ];
        $counts = array_fill_keys(array_keys($groups), 0);
        foreach ($orders as $order) {
            foreach ($groups as $label => $statuses) {
                if (in_array($order['status'], $statuses, true)) {
                    $counts[$label]++;
                    break;
                }
            }
        }

        $colors = ['#4f7df5', '#23b477', '#a65bea', '#f2a623', '#35c47a', '#e96773'];
        return ['labels' => array_keys($counts), 'values' => array_values($counts), 'colors' => $colors];
    }

    /** @return array{items:int,invested:float,imports:int} */
    private function collectionSummary(int $tenantId): array
    {
        $collection = $this->dashboard->collectionSummary($tenantId);

        return [
            'items' => (int) $collection['items'],
            'invested' => (float) $collection['invested'],
            'imports' => $collection['imports'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function orders(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        return $this->dashboard->orders($tenantId, $sellerId, $from, $to);
    }

    /** @return list<array<string, mixed>> */
    private function payments(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        return $this->dashboard->payments($tenantId, $sellerId, $from, $to);
    }

    /** @return list<array<string, mixed>> */
    private function garageItems(int $tenantId, ?int $sellerId, ?string $from, ?string $to): array
    {
        return $this->dashboard->garageItems($tenantId, $sellerId, $from, $to);
    }

    /**
     * @param list<array<string, mixed>> $orders
     * @param list<array<string, mixed>> $garageItems
     * @return array{labels:list<string>, total:list<float>, paid:list<float>}
     */
    private function monthly(array $orders, array $garageItems): array
    {
        $months = [];
        foreach ($orders as $order) {
            $date = $order['expected_delivery_date'] ?: $order['order_date'];
            $key = date('Y-m', strtotime((string) $date));
            $months[$key] ??= ['total' => 0.0, 'paid' => 0.0];
            $months[$key]['total'] += (float) $order['total'];
            $months[$key]['paid'] += (float) $order['paid'];
        }
        foreach ($garageItems as $item) {
            $total = (int) $item['quantity'] * (float) $item['unit_price'];
            $paid = min($total, (float) $item['paid']);
            $balance = max(0, $total - $paid);

            if ($paid > 0) {
                $paidKey = date('Y-m', strtotime((string) $item['acquired_at']));
                $months[$paidKey] ??= ['total' => 0.0, 'paid' => 0.0];
                $months[$paidKey]['paid'] += $paid;
            }
            if ($balance > 0) {
                $dueDate = $item['next_payment_date'] ?: $item['acquired_at'];
                $balanceKey = date('Y-m', strtotime((string) $dueDate));
                $months[$balanceKey] ??= ['total' => 0.0, 'paid' => 0.0];
                $months[$balanceKey]['total'] += $balance;
            }
        }
        ksort($months);
        return ['labels' => array_map(static fn (string $month): string => date('m/Y', strtotime($month . '-01')), array_keys($months)), 'total' => array_column($months, 'total'), 'paid' => array_column($months, 'paid')];
    }

    /** @param list<array<string, mixed>> $orders @return list<array<string, mixed>> */
    private function upcomingDeliveries(array $orders): array
    {
        $orders = array_filter(
            $orders,
            static fn (array $order): bool => ! empty($order['expected_delivery_date'])
                && $order['actual_delivery_date'] === null
                && ! in_array($order['status'], ['received', 'canceled'], true),
        );
        usort($orders, static fn (array $left, array $right): int => strcmp((string) $left['expected_delivery_date'], (string) $right['expected_delivery_date']));
        return array_slice($orders, 0, 5);
    }

    /** @return list<array<string, mixed>> */
    private function upcomingDue(int $tenantId, ?int $sellerId): array
    {
        $entries = $this->dashboard->upcomingPreorderDue($tenantId, $sellerId);
        foreach ($this->dashboard->upcomingGarageDue($tenantId, $sellerId) as $item) {
            $balance = max(0, (int) $item['quantity'] * (float) $item['unit_price'] - (float) $item['paid_amount']);
            if ($balance <= 0.0001) {
                continue;
            }
            $entries[] = [
                'reference' => 'Garagem: ' . $item['brand'] . ' ' . $item['product_name'],
                'product_names' => $item['brand'] . ' — ' . $item['product_name'],
                'seller_name' => $item['seller_name'],
                'amount' => $balance,
                'due_date' => $item['due_date'],
            ];
        }

        usort($entries, static fn (array $left, array $right): int => strcmp((string) ($left['due_date'] ?? '9999-12-31'), (string) ($right['due_date'] ?? '9999-12-31')));

        return array_slice($entries, 0, 5);
    }

    /** @return list<array{type:string,label:string,description:string,url:string}> */
    private function alerts(int $tenantId): array
    {
        $today = date('Y-m-d');
        $alerts = [];
        $counts = $this->dashboard->alertCounts($tenantId, $today, date('Y-m-d H:i:s', strtotime('-14 days')));
        $overdue = $counts['overdue'];
        if ($overdue > 0) {
            $alerts[] = ['type' => 'danger', 'label' => $overdue . ' pagamento(s) vencido(s)', 'description' => 'Existem parcelas com data de vencimento anterior a hoje.', 'url' => '/financeiro?status=overdue'];
        }
        $lateShipments = $counts['late_shipments'];
        if ($lateShipments > 0) {
            $alerts[] = ['type' => 'danger', 'label' => $lateShipments . ' envio(s) atrasado(s)', 'description' => 'A previsão de entrega já passou.', 'url' => '/entregas?late=1'];
        }
        $waiting = $counts['waiting'];
        if ($waiting > 0) {
            $alerts[] = ['type' => 'warning', 'label' => $waiting . ' item(ns) aguardando envio', 'description' => 'Estão disponíveis no vendedor há mais de 14 dias.', 'url' => '/entregas'];
        }
        return $alerts;
    }
}
