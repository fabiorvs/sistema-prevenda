<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDownPaymentToPreorders extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('preorders', ['down_payment_amount' => ['type' => 'DECIMAL','constraint' => '12,2','default' => 0,'after' => 'discount_amount']]);
    } public function down(): void
    {
        $this->forge->dropColumn('preorders', 'down_payment_amount');
    }
}
