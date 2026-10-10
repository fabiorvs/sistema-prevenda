<?php

namespace App\Modules\Catalog\Models;

use CodeIgniter\Model;

class ProductImageModel extends Model
{
    protected $table = 'product_images';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['tenant_id','product_id','path','mime_type','size_bytes','sort_order','is_primary'];
}
