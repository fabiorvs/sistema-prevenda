<?php

declare(strict_types=1);

namespace App\Modules\Administration\Services;

use CodeIgniter\Database\ConnectionInterface;

final class AdminAuditService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /**
     * @param array<string, scalar|null> $metadata
     */
    public function log(int $actorUserId, string $action, ?int $targetUserId = null, ?int $tenantId = null, array $metadata = []): void
    {
        $this->db->table('admin_audit_logs')->insert([
            'actor_user_id' => $actorUserId,
            'target_user_id' => $targetUserId,
            'tenant_id' => $tenantId,
            'action' => $action,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
