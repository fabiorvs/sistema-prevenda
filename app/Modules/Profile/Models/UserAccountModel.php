<?php

declare(strict_types=1);

namespace App\Modules\Profile\Models;

use CodeIgniter\Model;

final class UserAccountModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['username', 'active'];
}
