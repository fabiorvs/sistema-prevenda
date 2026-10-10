<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeliveryTracking extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('preorders', [
            'tracking_code' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'actual_delivery_date',
            ],
        ]);

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'preorder_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'preorder_item_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'old_status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'new_status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
            ],
            'received_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'tracking_code' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'expected_delivery_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'actual_delivery_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'actor_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'preorder_id']);
        $this->forge->addKey(['tenant_id', 'new_status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('preorder_id', 'preorders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('preorder_item_id', 'preorder_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('delivery_events', true);

        $this->db->table('preorders')
            ->where('status', 'open')
            ->update(['status' => 'awaiting_release']);
    }

    public function down(): void
    {
        $this->db->table('preorders')
            ->where('status', 'awaiting_release')
            ->update(['status' => 'open']);

        $this->forge->dropTable('delivery_events', true);
        $this->forge->dropColumn('preorders', 'tracking_code');
    }
}
