<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGarageTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'seller_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'quantity' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'acquired_at' => ['type' => 'DATE'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'stored'],
            'tracking_code' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'shipped_at' => ['type' => 'DATE', 'null' => true],
            'received_at' => ['type' => 'DATE', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'seller_id']);
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('seller_id', 'sellers', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('garage_items', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'garage_item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'method' => ['type' => 'VARCHAR', 'constraint' => 50],
            'paid_at' => ['type' => 'DATETIME'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'garage_item_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('garage_item_id', 'garage_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('garage_payments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('garage_payments', true);
        $this->forge->dropTable('garage_items', true);
    }
}
