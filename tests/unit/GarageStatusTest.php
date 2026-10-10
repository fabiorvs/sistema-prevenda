<?php

use App\Modules\Garage\Services\GarageStatus;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class GarageStatusTest extends CIUnitTestCase
{
    public function testGarageAcceptsOnlyKnownStatuses(): void
    {
        $this->assertTrue(GarageStatus::isValid('stored'));
        $this->assertTrue(GarageStatus::isValid('shipping'));
        $this->assertTrue(GarageStatus::isValid('received'));
        $this->assertTrue(GarageStatus::isValid('canceled'));
        $this->assertFalse(GarageStatus::isValid('unknown'));
    }
}
