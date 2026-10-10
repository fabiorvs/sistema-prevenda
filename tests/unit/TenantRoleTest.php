<?php

use App\Modules\Administration\Services\TenantRole;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TenantRoleTest extends CIUnitTestCase
{
    public function testOnlyKnownLocalRolesAreAccepted(): void
    {
        $this->assertTrue(TenantRole::isValid('owner'));
        $this->assertTrue(TenantRole::isValid('member'));
        $this->assertTrue(TenantRole::isValid('viewer'));
        $this->assertFalse(TenantRole::isValid('superadmin'));
    }
}
