<?php

declare(strict_types=1);

namespace App\Modules\Profile\Models;

use CodeIgniter\Model;

final class AuthIdentityModel extends Model
{
    protected $table = 'auth_identities';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'type', 'secret', 'secret2'];
}
