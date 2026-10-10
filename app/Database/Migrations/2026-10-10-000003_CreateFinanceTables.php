<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'preorder_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'category' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'method' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'confirmed'],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'proof_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['tenant_id', 'preorder_id']); $this->forge->addKey(['tenant_id', 'status']); $this->forge->addUniqueKey(['tenant_id', 'idempotency_key']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE'); $this->forge->addForeignKey('preorder_id', 'preorders', 'id', 'CASCADE', 'CASCADE'); $this->forge->createTable('payments', true);
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'preorder_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'due_date' => ['type' => 'DATE', 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['tenant_id', 'due_date']); $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE'); $this->forge->addForeignKey('preorder_id', 'preorders', 'id', 'CASCADE', 'CASCADE'); $this->forge->createTable('financial_entries', true);
        $this->forge->addField(['id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'tenant_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'payment_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'financial_entry_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'amount'=>['type'=>'DECIMAL','constraint'=>'12,2'],'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true]]);
        $this->forge->addKey('id',true); $this->forge->addKey(['tenant_id','payment_id']); $this->forge->addForeignKey('tenant_id','tenants','id','CASCADE','CASCADE'); $this->forge->addForeignKey('payment_id','payments','id','CASCADE','CASCADE'); $this->forge->addForeignKey('financial_entry_id','financial_entries','id','CASCADE','CASCADE'); $this->forge->createTable('payment_allocations',true);
    }
    public function down(): void { $this->forge->dropTable('payment_allocations', true); $this->forge->dropTable('financial_entries', true); $this->forge->dropTable('payments', true); }
}
