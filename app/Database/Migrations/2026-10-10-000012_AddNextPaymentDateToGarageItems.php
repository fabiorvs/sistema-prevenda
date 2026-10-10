<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddNextPaymentDateToGarageItems extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('garage_items', [
            'next_payment_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'acquired_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('garage_items', 'next_payment_date');
    }
}
