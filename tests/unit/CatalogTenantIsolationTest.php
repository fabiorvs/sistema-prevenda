<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CatalogTenantIsolationTest extends CIUnitTestCase
{
    public function testCatalogActionsAreScopedToTheActiveTenant(): void
    {
        $service = (string) file_get_contents(APPPATH . 'Modules/Catalog/Services/CatalogService.php');

        $this->assertStringContainsString("where('tenant_id', \$tenantId)", $service);
        $this->assertStringContainsString("['id' => \$id, 'tenant_id' => \$tenantId]", $service);
        $this->assertStringContainsString("['id' => \$imageId, 'tenant_id' => \$tenantId, 'product_id' => \$productId]", $service);
    }
}
