<?php
namespace App\Modules\Catalog\Models;
use CodeIgniter\Model;
class ProductModel extends Model { protected $table='products'; protected $primaryKey='id'; protected $useTimestamps=true; protected $allowedFields=['tenant_id','brand','name','manufacturer_code','scale','series','notes']; }
