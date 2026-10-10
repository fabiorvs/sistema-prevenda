<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class BackfillReceivedCollectionItems extends Migration
{
    public function up(): void
    {
        $this->db->query(
            'INSERT INTO collection_items '
            . '(tenant_id, shipment_item_id, product_id, quantity, unit_cost, freight_amount, received_at, created_at, updated_at) '
            . 'SELECT si.tenant_id, si.id, COALESCE(gi.product_id, pi.product_id), si.quantity, '
            . 'COALESCE(gi.unit_price, pi.unit_price), si.freight_amount, s.received_at, UTC_TIMESTAMP(), UTC_TIMESTAMP() '
            . 'FROM shipment_items si '
            . 'INNER JOIN shipments s ON s.id = si.shipment_id '
            . 'LEFT JOIN preorder_items pi ON pi.id = si.preorder_item_id '
            . 'LEFT JOIN garage_items gi ON gi.id = si.garage_item_id '
            . 'LEFT JOIN collection_items ci ON ci.shipment_item_id = si.id '
            . "WHERE s.status = 'received' AND ci.id IS NULL",
        );
    }

    public function down(): void
    {
        // Os itens recebidos fazem parte do histórico do usuário e não são removidos no rollback.
    }
}
