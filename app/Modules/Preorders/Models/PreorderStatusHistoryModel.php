<?php

declare(strict_types=1);

namespace App\Modules\Preorders\Models;

use CodeIgniter\Model;

final class PreorderStatusHistoryModel extends Model
{
    protected $table = 'preorder_status_history';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'tenant_id',
        'preorder_id',
        'old_status',
        'new_status',
        'actor_user_id',
        'created_at',
    ];
}
