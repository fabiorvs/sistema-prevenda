<?php

declare(strict_types=1);

namespace App\Modules\Garage\Models;

use CodeIgniter\Model;

final class GaragePaymentModel extends Model
{
    protected $table = 'garage_payments';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id',
        'garage_item_id',
        'amount',
        'method',
        'paid_at',
        'notes',
    ];
}
