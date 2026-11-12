<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddShipmentTrackingProviders extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('shipments', [
            'tracking_provider' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'tracking_code'],
            'tracking_carrier' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'tracking_provider'],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'shipment_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 40],
            'carrier' => ['type' => 'VARCHAR', 'constraint' => 80],
            'tracking_code' => ['type' => 'VARCHAR', 'constraint' => 120],
            'response_payload' => ['type' => 'TEXT', 'null' => true],
            'error_message' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'shipment_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('shipment_id', 'shipments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('shipment_tracking_queries', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('shipment_tracking_queries', true);
        $this->forge->dropColumn('shipments', ['tracking_provider', 'tracking_carrier']);
    }
}
