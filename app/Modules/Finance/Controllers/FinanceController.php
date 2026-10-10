<?php

declare(strict_types=1);

namespace App\Modules\Finance\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class FinanceController extends BaseController
{
    public function index(): string
    {
        $tenantId = $this->tenant();
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $query = db_connect()
            ->table('financial_entries')
            ->select('financial_entries.*, preorders.reference, sellers.name AS seller_name, COALESCE(SUM(payment_allocations.amount), 0) AS paid_amount', false)
            ->join('preorders', 'preorders.id = financial_entries.preorder_id')
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->join('payment_allocations', 'payment_allocations.financial_entry_id = financial_entries.id', 'left')
            ->where('financial_entries.tenant_id', $tenantId)
            ->groupBy('financial_entries.id')
            ->orderBy('financial_entries.due_date IS NULL', 'ASC', false)
            ->orderBy('financial_entries.due_date', 'ASC');

        if ($search !== '') {
            $query
                ->groupStart()
                ->like('financial_entries.description', $search)
                ->orLike('preorders.reference', $search)
                ->orLike('sellers.name', $search)
                ->groupEnd();
        }
        if (in_array($status, ['pending', 'partial', 'paid', 'overdue'], true)) {
            if ($status === 'overdue') {
                $query
                    ->where('financial_entries.due_date <', date('Y-m-d'))
                    ->whereNotIn('financial_entries.status', ['paid']);
            } else {
                $query->where('financial_entries.status', $status);
            }
        }

        $entries = $query->get()->getResultArray();
        $summary = ['total' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'overdue' => 0.0];
        foreach ($entries as &$entry) {
            $entry['balance'] = max(0, (float) $entry['amount'] - (float) $entry['paid_amount']);
            $entry['display_status'] = $this->displayStatus($entry);
            $entry['source'] = 'preorder';
            $entry['source_label'] = $entry['reference'];
            $entry['source_url'] = '/pre-vendas/' . $entry['preorder_id'];
            $entry['payment_url'] = '/financeiro/contas/' . $entry['id'] . '/pagamento';
            $entry['display_date'] = $entry['due_date'];
            $summary['total'] += (float) $entry['amount'];
            $summary['paid'] += (float) $entry['paid_amount'];
            $summary['balance'] += (float) $entry['balance'];
            if ($entry['display_status'] === 'overdue') {
                $summary['overdue'] += (float) $entry['balance'];
            }
        }
        unset($entry);

        $garageEntries = db_connect()
            ->table('garage_items')
            ->select('garage_items.id, garage_items.acquired_at, garage_items.next_payment_date, garage_items.quantity, garage_items.unit_price, garage_items.status AS garage_status, sellers.name AS seller_name, products.name AS product_name, products.brand, COALESCE(SUM(garage_payments.amount), 0) AS paid_amount', false)
            ->join('sellers', 'sellers.id = garage_items.seller_id')
            ->join('products', 'products.id = garage_items.product_id')
            ->join('garage_payments', 'garage_payments.garage_item_id = garage_items.id', 'left')
            ->where('garage_items.tenant_id', $tenantId)
            ->where('garage_items.status !=', 'canceled')
            ->groupBy('garage_items.id')
            ->get()
            ->getResultArray();

        foreach ($garageEntries as $garageEntry) {
            $amount = (int) $garageEntry['quantity'] * (float) $garageEntry['unit_price'];
            $entry = [
                'source' => 'garage',
                'source_label' => 'Garagem',
                'source_url' => '/garagem/' . $garageEntry['id'],
                'payment_url' => '/garagem/' . $garageEntry['id'] . '#registrar-pagamento',
                'due_date' => $garageEntry['next_payment_date'],
                'display_date' => $garageEntry['next_payment_date'] ?: $garageEntry['acquired_at'],
                'description' => 'Garagem: ' . $garageEntry['brand'] . ' ' . $garageEntry['product_name'],
                'seller_name' => $garageEntry['seller_name'],
                'amount' => $amount,
                'paid_amount' => (float) $garageEntry['paid_amount'],
                'balance' => max(0, $amount - (float) $garageEntry['paid_amount']),
                'status' => 'pending',
            ];
            $entry['display_status'] = $this->displayStatus($entry);

            $matchesSearch = $search === '' || str_contains(
                mb_strtolower($entry['description'] . ' ' . $entry['seller_name']),
                mb_strtolower($search),
            );
            $matchesStatus = $status === '' || $entry['display_status'] === $status;
            if (! $matchesSearch || ! $matchesStatus) {
                continue;
            }

            $entries[] = $entry;
            $summary['total'] += $amount;
            $summary['paid'] += (float) $entry['paid_amount'];
            $summary['balance'] += (float) $entry['balance'];
            if ($entry['display_status'] === 'overdue') {
                $summary['overdue'] += (float) $entry['balance'];
            }
        }

        usort($entries, static function (array $left, array $right): int {
            return strcmp((string) ($left['due_date'] ?? '9999-12-31'), (string) ($right['due_date'] ?? '9999-12-31'));
        });

        $garage = db_connect()->table('garage_items')
            ->select('COALESCE(SUM(garage_items.quantity * garage_items.unit_price), 0) AS total', false)
            ->where('garage_items.tenant_id', $tenantId)
            ->whereNotIn('garage_items.status', ['received', 'canceled'])
            ->get()
            ->getRowArray();
        $garagePayments = db_connect()->table('garage_payments')
            ->selectSum('amount')
            ->join('garage_items', 'garage_items.id = garage_payments.garage_item_id')
            ->where('garage_payments.tenant_id', $tenantId)
            ->whereNotIn('garage_items.status', ['received', 'canceled'])
            ->get()
            ->getRowArray();
        $garage['paid'] = (float) ($garagePayments['amount'] ?? 0);
        $garage['balance'] = max(0, (float) $garage['total'] - (float) $garage['paid']);

        $today = date('Y-m-d');
        $nextThirtyDays = date('Y-m-d', strtotime('+30 days'));
        $overview = ['due_now' => 0.0, 'next_30_days' => 0.0, 'invested_month' => 0.0];
        foreach ($entries as $entry) {
            if ((float) $entry['balance'] <= 0.0001 || empty($entry['due_date'])) {
                continue;
            }
            if ($entry['due_date'] <= $today) {
                $overview['due_now'] += (float) $entry['balance'];
            } elseif ($entry['due_date'] <= $nextThirtyDays) {
                $overview['next_30_days'] += (float) $entry['balance'];
            }
        }
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd = date('Y-m-t 23:59:59');
        $preorderInvestment = (float) (db_connect()->table('payments')
            ->select('COALESCE(SUM(CASE WHEN type = "refund" THEN -amount ELSE amount END), 0) AS total', false)
            ->where(['tenant_id' => $tenantId, 'status' => 'confirmed'])
            ->where('paid_at >=', $monthStart)->where('paid_at <=', $monthEnd)->get()->getRowArray()['total'] ?? 0);
        $garageInvestment = (float) (db_connect()->table('garage_payments')
            ->selectSum('amount')->where('tenant_id', $tenantId)
            ->where('paid_at >=', $monthStart)->where('paid_at <=', $monthEnd)->get()->getRowArray()['amount'] ?? 0);
        $freightInvestment = (float) (db_connect()->table('shipments')
            ->selectSum('shipping_amount')->where('tenant_id', $tenantId)
            ->where('status !=', 'canceled')->where('shipped_at >=', date('Y-m-01'))->where('shipped_at <=', date('Y-m-t'))
            ->get()->getRowArray()['shipping_amount'] ?? 0);
        $overview['invested_month'] = $preorderInvestment + $garageInvestment + $freightInvestment;

        return view('finance/index', [
            'entries' => $entries,
            'summary' => $summary,
            'garage' => $garage,
            'overview' => $overview,
            'filters' => ['q' => $search, 'status' => $status],
        ]);
    }

    public function payments(): string
    {
        $payments = db_connect()
            ->table('payments')
            ->select('payments.*, preorders.reference')
            ->join('preorders', 'preorders.id = payments.preorder_id')
            ->where('payments.tenant_id', $this->tenant())
            ->orderBy('payments.paid_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('finance/payments', ['payments' => $payments]);
    }

    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function displayStatus(array $entry): string
    {
        if ((float) $entry['balance'] <= 0.0001 || $entry['status'] === 'paid') {
            return 'paid';
        }
        if ($entry['due_date'] && $entry['due_date'] < date('Y-m-d')) {
            return 'overdue';
        }
        if ((float) $entry['paid_amount'] > 0) {
            return 'partial';
        }

        return 'pending';
    }
}
