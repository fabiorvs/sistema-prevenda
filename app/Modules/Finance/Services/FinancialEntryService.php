<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use CodeIgniter\Database\ConnectionInterface;

final class FinancialEntryService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function createForPreorder(int $tenantId, int $preorderId): void
    {
        $this->syncForPreorder($tenantId, $preorderId);
    }

    public function syncForPreorder(int $tenantId, int $preorderId): void
    {
        $preorder = $this->db->table('preorders')
            ->where(['id' => $preorderId, 'tenant_id' => $tenantId])
            ->get()
            ->getRowArray();

        if ($preorder === null) {
            throw new \RuntimeException('Pré-venda não encontrada para a conta ativa.');
        }

        $items = $this->db->table('preorder_items')
            ->select('COALESCE(SUM(quantity * unit_price), 0) AS items_total', false)
            ->where(['preorder_id' => $preorderId, 'tenant_id' => $tenantId])
            ->get()
            ->getRowArray();
        $amount = max(
            0,
            (float) ($items['items_total'] ?? 0)
                + (float) $preorder['shipping_amount']
                - (float) $preorder['discount_amount']
                - (float) $preorder['down_payment_amount'],
        );

        $entry = $this->db->table('financial_entries')
            ->where(['tenant_id' => $tenantId, 'preorder_id' => $preorderId])
            ->orderBy('id')
            ->get()
            ->getRowArray();

        if ($entry === null) {
            if ($amount <= 0.0001) {
                return;
            }

            $now = gmdate('Y-m-d H:i:s');
            $this->db->table('financial_entries')->insert([
                'tenant_id' => $tenantId,
                'preorder_id' => $preorderId,
                'description' => 'Saldo da pré-venda ' . $preorder['reference'],
                'due_date' => $preorder['expected_delivery_date'],
                'amount' => number_format($amount, 2, '.', ''),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $allocated = (float) ($this->db->table('payment_allocations')
            ->select('COALESCE(SUM(amount), 0) AS paid_amount', false)
            ->where(['tenant_id' => $tenantId, 'financial_entry_id' => $entry['id']])
            ->get()
            ->getRowArray()['paid_amount'] ?? 0);
        $status = match (true) {
            $allocated >= $amount - 0.0001 => 'paid',
            $allocated > 0 => 'partial',
            default => 'pending',
        };

        $this->db->table('financial_entries')
            ->where(['id' => $entry['id'], 'tenant_id' => $tenantId])
            ->update([
                'due_date' => $preorder['expected_delivery_date'],
                'amount' => number_format($amount, 2, '.', ''),
                'status' => $status,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ]);
    }
}
