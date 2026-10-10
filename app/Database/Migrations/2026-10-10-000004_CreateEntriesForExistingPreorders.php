<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEntriesForExistingPreorders extends Migration
{
    public function up(): void
    {
        $orders = $this->db->table('preorders')->get()->getResultArray();

        foreach ($orders as $order) {
            $alreadyCreated = $this->db->table('financial_entries')
                ->where(['tenant_id' => $order['tenant_id'], 'preorder_id' => $order['id']])
                ->countAllResults();

            if ($alreadyCreated > 0) {
                continue;
            }

            $items = $this->db->table('preorder_items')
                ->select('SUM(quantity * unit_price) AS items_total', false)
                ->where('preorder_id', $order['id'])
                ->get()
                ->getRowArray();

            $total = (float) ($items['items_total'] ?? 0)
                + (float) $order['shipping_amount']
                - (float) $order['discount_amount'];
            $balance = max(0, $total - (float) $order['down_payment_amount']);

            if ($balance === 0.0) {
                continue;
            }

            $now = gmdate('Y-m-d H:i:s');
            $this->db->table('financial_entries')->insert([
                'tenant_id' => $order['tenant_id'],
                'preorder_id' => $order['id'],
                'description' => 'Saldo da pré-venda ' . $order['reference'],
                'due_date' => $order['expected_delivery_date'],
                'amount' => number_format($balance, 2, '.', ''),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->db->table('financial_entries')->like('description', 'Saldo da pré-venda ', 'after')->delete();
    }
}
