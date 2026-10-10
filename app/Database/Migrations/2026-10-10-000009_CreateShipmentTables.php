<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateShipmentTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'seller_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 40],
            'carrier' => ['type' => 'VARCHAR', 'constraint' => 120],
            'tracking_code' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'shipping_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'organized'],
            'shipped_at' => ['type' => 'DATE', 'null' => true],
            'received_at' => ['type' => 'DATE', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'seller_id']);
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addUniqueKey(['tenant_id', 'reference']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('seller_id', 'sellers', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('shipments', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'shipment_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'preorder_item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'quantity' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'shipment_id']);
        $this->forge->addUniqueKey(['shipment_id', 'preorder_item_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('shipment_id', 'shipments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('preorder_item_id', 'preorder_items', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('shipment_items', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('shipment_items', true);
        $this->forge->dropTable('shipments', true);
    }
}
