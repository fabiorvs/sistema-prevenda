<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateDirectPurchases extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'store_name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'purchased_at' => ['type' => 'DATE'],
            'quantity' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'location' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['tenant_id', 'purchased_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('direct_purchases', true);
        $this->db->query('ALTER TABLE collection_items MODIFY shipment_item_id BIGINT UNSIGNED NULL, ADD direct_purchase_id BIGINT UNSIGNED NULL AFTER shipment_item_id, ADD UNIQUE KEY collection_direct_purchase_unique (direct_purchase_id), ADD CONSTRAINT collection_direct_purchase_foreign FOREIGN KEY (direct_purchase_id) REFERENCES direct_purchases(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }
    public function down(): void
    {
        $this->db->query('ALTER TABLE collection_items DROP FOREIGN KEY collection_direct_purchase_foreign, DROP INDEX collection_direct_purchase_unique, DROP COLUMN direct_purchase_id, MODIFY shipment_item_id BIGINT UNSIGNED NOT NULL');
        $this->forge->dropTable('direct_purchases', true);
    }
}
