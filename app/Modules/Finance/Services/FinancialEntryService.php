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
        $existing = $this->db->table('financial_entries')
            ->where(['tenant_id' => $tenantId, 'preorder_id' => $preorderId])
            ->countAllResults();

        if ($existing > 0) {
            return;
        }

        $preorder = $this->db->table('preorders')
            ->where(['id' => $preorderId, 'tenant_id' => $tenantId])
            ->get()
            ->getRowArray();

        if (! $preorder) {
            throw new \RuntimeException('Pré-venda não encontrada para a conta ativa.');
        }

        $items = $this->db->table('preorder_items')
            ->select('SUM(quantity * unit_price) AS items_total', false)
            ->where('preorder_id', $preorderId)
            ->get()
            ->getRowArray();

        $total = (float) ($items['items_total'] ?? 0)
            + (float) $preorder['shipping_amount']
            - (float) $preorder['discount_amount'];
        $balance = max(0, $total - (float) $preorder['down_payment_amount']);

        if ($balance === 0.0) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $this->db->table('financial_entries')->insert([
            'tenant_id' => $tenantId,
            'preorder_id' => $preorderId,
            'description' => 'Saldo da pré-venda ' . $preorder['reference'],
            'due_date' => $preorder['expected_delivery_date'],
            'amount' => number_format($balance, 2, '.', ''),
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
