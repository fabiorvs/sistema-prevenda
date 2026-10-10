<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddExpectedDeliveryDateToShipments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('shipments', [
            'expected_delivery_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'shipped_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('shipments', 'expected_delivery_date');
    }
}
