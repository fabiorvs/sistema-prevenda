<?php

use App\Modules\Deliveries\Services\TrackingProvider;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TrackingProviderTest extends CIUnitTestCase
{
    public function testWoncaOnlyAllowsItsKnownCarrier(): void
    {
        $this->assertTrue(TrackingProvider::isValid(TrackingProvider::WONCA));
        $this->assertTrue(TrackingProvider::supportsCarrier(TrackingProvider::WONCA, 'CARRIER_CORREIOS'));
        $this->assertFalse(TrackingProvider::supportsCarrier(TrackingProvider::WONCA, 'CARRIER_UNKNOWN'));
    }

    public function testNoProviderDoesNotRequireACarrier(): void
    {
        $this->assertTrue(TrackingProvider::supportsCarrier(TrackingProvider::NONE, null));
    }
}
