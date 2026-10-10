<?php

use App\Modules\Deliveries\Services\DeliveryStatus;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DeliveryStatusTest extends CIUnitTestCase
{
    public function testAllItemsReceivedMarksDeliveryAsReceived(): void
    {
        $status = DeliveryStatus::fromItems([
            ['quantity' => 2, 'received_quantity' => 2],
            ['quantity' => 1, 'received_quantity' => 1],
        ]);

        $this->assertSame(DeliveryStatus::RECEIVED, $status);
    }

    public function testPartialReceiptKeepsDeliveryPartial(): void
    {
        $status = DeliveryStatus::fromItems([
            ['quantity' => 2, 'received_quantity' => 1],
            ['quantity' => 1, 'received_quantity' => 0],
        ]);

        $this->assertSame(DeliveryStatus::PARTIALLY_RECEIVED, $status);
    }

    public function testNoReceiptKeepsDeliveryAwaitingRelease(): void
    {
        $status = DeliveryStatus::fromItems([
            ['quantity' => 1, 'received_quantity' => 0],
        ]);

        $this->assertSame(DeliveryStatus::AWAITING_RELEASE, $status);
    }
}
