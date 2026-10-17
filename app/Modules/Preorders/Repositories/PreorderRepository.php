<?php

declare(strict_types=1);

namespace App\Modules\Preorders\Repositories;

use App\Modules\Preorders\Models\PreorderItemModel;
use App\Modules\Preorders\Models\PreorderModel;
use App\Modules\Preorders\Models\PreorderStatusHistoryModel;
use CodeIgniter\Database\ConnectionInterface;

final class PreorderRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** @return array<string,mixed>|null */
    public function findForTenant(int $tenantId, int $preorderId): ?array
    {
        return (new PreorderModel())
            ->where(['id' => $preorderId, 'tenant_id' => $tenantId])
            ->first();
    }

    /** @return list<array<string,mixed>> */
    public function itemsForTenant(int $tenantId, int $preorderId): array
    {
        return (new PreorderItemModel())
            ->where(['preorder_id' => $preorderId, 'tenant_id' => $tenantId])
            ->orderBy('id')
            ->findAll();
    }

    /** @return list<array<string,mixed>> */
    public function sellersForTenant(int $tenantId): array
    {
        return $this->db->table('sellers')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string,mixed>> */
    public function productsForTenant(int $tenantId): array
    {
        return $this->db->table('products')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->getResultArray();
    }

    public function save(PreorderModel $model, array $data): int
    {
        $model->insert($data);

        return (int) $model->getInsertID();
    }

    public function updateForTenant(int $tenantId, int $preorderId, array $data): bool
    {
        return (new PreorderModel())
            ->where(['id' => $preorderId, 'tenant_id' => $tenantId])
            ->set($data)
            ->update();
    }

    public function addHistory(int $tenantId, int $preorderId, array $data): void
    {
        (new PreorderStatusHistoryModel())->insert($data + [
            'tenant_id' => $tenantId,
            'preorder_id' => $preorderId,
        ]);
    }
}
