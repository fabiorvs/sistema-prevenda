<?php
namespace App\Modules\Sellers\Models;
use CodeIgniter\Model;
class SellerModel extends Model { protected $table='sellers'; protected $primaryKey='id'; protected $useTimestamps=true; protected $allowedFields=['tenant_id','name','contact_name','whatsapp','instagram','website','image_path','image_mime_type','notes']; }
