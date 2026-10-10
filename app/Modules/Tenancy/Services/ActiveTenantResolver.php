<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Services;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Session\Session;

final class ActiveTenantResolver
{
    public function __construct(private readonly ConnectionInterface $db, private readonly Session $session)
    {
    }

    public function forUser(int $userId): ?int
    {
        $tenantId = $this->session->get('active_tenant_id');
        if ($tenantId && $this->belongsToUser((int) $tenantId, $userId)) {
            return (int) $tenantId;
        }
        $row = $this->db->table('tenant_users')->select('tenant_id')->where(['user_id' => $userId, 'status' => 'active'])->orderBy('tenant_id')->get(1)->getRowArray();
        if ($row === null) {
            return null;
        }
        $this->session->set('active_tenant_id', (int) $row['tenant_id']);
        return (int) $row['tenant_id'];
    }

    public function belongsToUser(int $tenantId, int $userId): bool
    {
        return $this->db->table('tenant_users')->where(['tenant_id' => $tenantId, 'user_id' => $userId, 'status' => 'active'])->countAllResults() === 1;
    }
}
