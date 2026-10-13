<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CatalogTenantIsolationTest extends CIUnitTestCase
{
    public function testCatalogActionsAreScopedToTheActiveTenant(): void
    {
        $controller = (string) file_get_contents(APPPATH . 'Modules/Catalog/Controllers/ProductsController.php');

        $this->assertStringContainsString("where('tenant_id', \$tenantId)", $controller);
        $this->assertStringContainsString("'tenant_id' => \$this->tenant(), 'product_id' => \$product['id']", $controller);
        $this->assertStringContainsString("['id' => \$id, 'tenant_id' => \$this->tenant()]", $controller);
    }
}
