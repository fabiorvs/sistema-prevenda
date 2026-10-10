<?php

declare(strict_types=1);

namespace App\Modules\Preorders\Models;

use CodeIgniter\Model;

final class PreorderModel extends Model
{
    protected $table = 'preorders';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id',
        'seller_id',
        'reference',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'tracking_code',
        'status',
        'shipping_amount',
        'discount_amount',
        'down_payment_amount',
        'down_payment_method',
        'notes',
    ];
}
