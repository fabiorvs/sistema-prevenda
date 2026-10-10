<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AllowGarageItemsInShipments extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('shipment_items', [
            'preorder_item_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
        ]);
        $this->forge->addColumn('shipment_items', [
            'garage_item_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'after' => 'preorder_item_id',
            ],
        ]);
        $this->db->query(
            'ALTER TABLE shipment_items '
            . 'ADD UNIQUE KEY shipment_garage_item_unique (shipment_id, garage_item_id), '
            . 'ADD CONSTRAINT shipment_items_garage_item_id_foreign '
            . 'FOREIGN KEY (garage_item_id) REFERENCES garage_items(id) ON DELETE RESTRICT ON UPDATE CASCADE',
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE shipment_items '
            . 'DROP FOREIGN KEY shipment_items_garage_item_id_foreign, '
            . 'DROP INDEX shipment_garage_item_unique',
        );
        $this->forge->dropColumn('shipment_items', 'garage_item_id');
        $this->forge->modifyColumn('shipment_items', [
            'preorder_item_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
        ]);
    }
}
