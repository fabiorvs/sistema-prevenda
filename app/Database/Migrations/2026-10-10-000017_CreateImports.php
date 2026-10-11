<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateImports extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'supplier_name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'purchased_at' => ['type' => 'DATE'],
            'expected_at' => ['type' => 'DATE', 'null' => true],
            'received_at' => ['type' => 'DATE', 'null' => true],
            'currency' => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'USD'],
            'exchange_rate' => ['type' => 'DECIMAL', 'constraint' => '12,4', 'default' => 1],
            'item_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'shipping_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'tracking_code' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'carrier' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'purchased'],
            'location' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('imports', true);

        $this->db->query('ALTER TABLE collection_items ADD import_id BIGINT UNSIGNED NULL AFTER direct_purchase_id, ADD UNIQUE KEY collection_import_unique (import_id), ADD CONSTRAINT collection_import_foreign FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE collection_items DROP FOREIGN KEY collection_import_foreign, DROP INDEX collection_import_unique, DROP COLUMN import_id');
        $this->forge->dropTable('imports', true);
    }
}
