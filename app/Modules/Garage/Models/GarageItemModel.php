<?php

declare(strict_types=1);

namespace App\Modules\Garage\Models;

use CodeIgniter\Model;

final class GarageItemModel extends Model
{
    protected $table = 'garage_items';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id',
        'seller_id',
        'product_id',
        'quantity',
        'unit_price',
        'acquired_at',
        'next_payment_date',
        'status',
        'tracking_code',
        'shipped_at',
        'received_at',
        'notes',
    ];
}
