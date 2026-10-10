<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddFreightAndCollection extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('shipment_items', [
            'freight_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'quantity'],
        ]);
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'shipment_item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'quantity' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'freight_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'received_at' => ['type' => 'DATE'],
            'location' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['shipment_item_id']);
        $this->forge->addKey(['tenant_id', 'received_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('shipment_item_id', 'shipment_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('collection_items', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('collection_items', true);
        $this->forge->dropColumn('shipment_items', 'freight_amount');
    }
}
