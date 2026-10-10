<?php
namespace App\Modules\Preorders\Models;
use CodeIgniter\Model;
class PreorderModel extends Model { protected $table='preorders';protected $primaryKey='id';protected $useTimestamps=true;protected $allowedFields=['tenant_id','seller_id','reference','order_date','expected_delivery_date','actual_delivery_date','status','shipping_amount','discount_amount','down_payment_amount','notes']; }
