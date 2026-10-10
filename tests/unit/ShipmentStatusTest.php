<?php

use App\Modules\Deliveries\Services\ShipmentStatus;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ShipmentStatusTest extends CIUnitTestCase
{
    public function testOnlyKnownShipmentStatusesAreAccepted(): void
    {
        $this->assertTrue(ShipmentStatus::isValid('organized'));
        $this->assertTrue(ShipmentStatus::isValid('shipped'));
        $this->assertTrue(ShipmentStatus::isValid('received'));
        $this->assertTrue(ShipmentStatus::isValid('canceled'));
        $this->assertFalse(ShipmentStatus::isValid('unknown'));
    }
}
