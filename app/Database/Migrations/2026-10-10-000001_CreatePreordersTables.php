<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePreordersTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField(['id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'tenant_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'seller_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'reference'=>['type'=>'VARCHAR','constraint'=>40],'order_date'=>['type'=>'DATE'],'expected_delivery_date'=>['type'=>'DATE','null'=>true],'actual_delivery_date'=>['type'=>'DATE','null'=>true],'status'=>['type'=>'VARCHAR','constraint'=>30,'default'=>'open'],'shipping_amount'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0],'discount_amount'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0],'notes'=>['type'=>'TEXT','null'=>true],'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true]]);
        $this->forge->addKey('id',true);$this->forge->addKey(['tenant_id','seller_id']);$this->forge->addKey(['tenant_id','status']);$this->forge->addForeignKey('tenant_id','tenants','id','CASCADE','CASCADE');$this->forge->addForeignKey('seller_id','sellers','id','RESTRICT','CASCADE');$this->forge->createTable('preorders',true);
        $this->forge->addField(['id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'tenant_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'preorder_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'product_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'quantity'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],'unit_price'=>['type'=>'DECIMAL','constraint'=>'12,2'],'received_quantity'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'default'=>0],'expected_delivery_date'=>['type'=>'DATE','null'=>true],'status'=>['type'=>'VARCHAR','constraint'=>30,'default'=>'open'],'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true]]);
        $this->forge->addKey('id',true);$this->forge->addKey(['tenant_id','preorder_id']);$this->forge->addForeignKey('tenant_id','tenants','id','CASCADE','CASCADE');$this->forge->addForeignKey('preorder_id','preorders','id','CASCADE','CASCADE');$this->forge->addForeignKey('product_id','products','id','RESTRICT','CASCADE');$this->forge->createTable('preorder_items',true);
        $this->forge->addField(['id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'tenant_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'preorder_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'old_status'=>['type'=>'VARCHAR','constraint'=>30,'null'=>true],'new_status'=>['type'=>'VARCHAR','constraint'=>30],'actor_user_id'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],'created_at'=>['type'=>'DATETIME','null'=>true]]);
        $this->forge->addKey('id',true);$this->forge->addKey(['tenant_id','preorder_id']);$this->forge->addForeignKey('tenant_id','tenants','id','CASCADE','CASCADE');$this->forge->addForeignKey('preorder_id','preorders','id','CASCADE','CASCADE');$this->forge->addForeignKey('actor_user_id','users','id','RESTRICT','CASCADE');$this->forge->createTable('preorder_status_history',true);
    }
    public function down(): void {$this->forge->dropTable('preorder_status_history',true);$this->forge->dropTable('preorder_items',true);$this->forge->dropTable('preorders',true);}
}
