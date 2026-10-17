<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use CodeIgniter\Database\ConnectionInterface;

final class FinancialEntryRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function preorderForTenant(int $tenantId, int $preorderId): ?array
    {
        return $this->db->table('preorders')->where(['id' => $preorderId, 'tenant_id' => $tenantId])->get()->getRowArray();
    }

    public function itemsTotal(int $tenantId, int $preorderId): string
    {
        return (string) ($this->db->table('preorder_items')->select('COALESCE(SUM(quantity * unit_price), 0) AS items_total', false)->where(['preorder_id' => $preorderId, 'tenant_id' => $tenantId])->get()->getRowArray()['items_total'] ?? '0');
    }

    public function findForPreorder(int $tenantId, int $preorderId): ?array
    {
        return $this->db->table('financial_entries')->where(['tenant_id' => $tenantId, 'preorder_id' => $preorderId])->orderBy('id')->get()->getRowArray();
    }

    public function create(array $data): void
    {
        $this->db->table('financial_entries')->insert($data);
    }

    public function allocatedAmount(int $tenantId, int $entryId): string
    {
        return (string) ($this->db->table('payment_allocations')->select('COALESCE(SUM(amount), 0) AS paid_amount', false)->where(['tenant_id' => $tenantId, 'financial_entry_id' => $entryId])->get()->getRowArray()['paid_amount'] ?? '0');
    }

    public function updateForTenant(int $tenantId, int $entryId, array $data): void
    {
        $this->db->table('financial_entries')->where(['id' => $entryId, 'tenant_id' => $tenantId])->update($data);
    }
}
