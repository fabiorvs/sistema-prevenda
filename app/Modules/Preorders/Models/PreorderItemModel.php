<?php

namespace App\Modules\Preorders\Models;

use CodeIgniter\Model;

class PreorderItemModel extends Model
{
    protected $table = 'preorder_items';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['tenant_id','preorder_id','product_id','quantity','unit_price','received_quantity','expected_delivery_date','status'];
}
