<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PreorderRepositoryTenantScopeTest extends CIUnitTestCase
{
    public function testTenantScopedMethodsRequireTheTenantIdentifier(): void
    {
        $repository = (string) file_get_contents(
            APPPATH . 'Modules/Preorders/Repositories/PreorderRepository.php',
        );

        $this->assertStringContainsString('findForTenant(int $tenantId, int $preorderId)', $repository);
        $this->assertStringContainsString("['id' => \$preorderId, 'tenant_id' => \$tenantId]", $repository);
        $this->assertStringContainsString("['preorder_id' => \$preorderId, 'tenant_id' => \$tenantId]", $repository);
    }
}
