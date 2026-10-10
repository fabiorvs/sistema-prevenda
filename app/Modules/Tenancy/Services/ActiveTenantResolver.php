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
        $row = $this->db->table('tenant_users')
            ->select('tenant_users.tenant_id')
            ->join('tenants', 'tenants.id = tenant_users.tenant_id')
            ->join('users', 'users.id = tenant_users.user_id')
            ->where(['tenant_users.user_id' => $userId, 'tenant_users.status' => 'active', 'tenants.status' => 'active', 'users.active' => 1])
            ->orderBy('tenant_users.tenant_id')
            ->get(1)
            ->getRowArray();
        if ($row === null) {
            return null;
        }
        $this->session->set('active_tenant_id', (int) $row['tenant_id']);
        return (int) $row['tenant_id'];
    }

    public function belongsToUser(int $tenantId, int $userId): bool
    {
        return $this->db->table('tenant_users')
            ->join('tenants', 'tenants.id = tenant_users.tenant_id')
            ->join('users', 'users.id = tenant_users.user_id')
            ->where(['tenant_users.tenant_id' => $tenantId, 'tenant_users.user_id' => $userId, 'tenant_users.status' => 'active', 'tenants.status' => 'active', 'users.active' => 1])
            ->countAllResults() === 1;
    }
}
