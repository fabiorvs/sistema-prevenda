<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Services;

use CodeIgniter\Database\ConnectionInterface;
use RuntimeException;

final class TenantProvisioner
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function provision(int $userId, string $name): int
    {
        $existing = $this->db->table('tenant_users')->select('tenant_id')->where('user_id', $userId)->get()->getRowArray();
        if ($existing !== null) {
            return (int) $existing['tenant_id'];
        }
        $now = gmdate('Y-m-d H:i:s');
        $this->db->transStart();
        $this->db->table('tenants')->insert(['name' => trim($name) !== '' ? trim($name) : 'Minha coleção', 'type' => 'personal', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $tenantId = (int) $this->db->insertID();
        if ($tenantId === 0) {
            throw new RuntimeException('Não foi possível criar o tenant.');
        }
        $this->db->table('tenant_users')->insert(['tenant_id' => $tenantId, 'user_id' => $userId, 'role' => 'owner', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $freePlan = $this->db->table('plans')->select('id')->where('slug', 'free')->get()->getRowArray();
        if ($freePlan !== null) {
            $this->db->table('tenant_plan_assignments')->insert([
                'tenant_id' => $tenantId,
                'plan_id' => $freePlan['id'],
                'status' => 'active',
                'starts_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->db->table('user_preferences')->ignore(true)->insert(['user_id' => $userId, 'currency' => 'BRL', 'timezone' => 'America/Sao_Paulo', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new RuntimeException('Não foi possível provisionar a conta.');
        }
        return $tenantId;
    }
}
