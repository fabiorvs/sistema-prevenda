<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddDownPaymentMethodToPreorders extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('preorders', [
            'down_payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'down_payment_amount',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('preorders', 'down_payment_method');
    }
}
