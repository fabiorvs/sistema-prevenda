<?php

declare(strict_types=1);

namespace App\Modules\Administration\Repositories;

use CodeIgniter\Database\ConnectionInterface;

final class AdminAuditRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** @param array<string, scalar|null> $data */
    public function create(array $data): void
    {
        $this->db->table('admin_audit_logs')->insert($data);
    }
}
