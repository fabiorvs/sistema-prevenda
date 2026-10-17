<?php

declare(strict_types=1);

use App\Modules\Catalog\Services\CatalogService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class CatalogTenantIsolationIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    protected function createTenant(string $name): int
    {
        $this->db->table('tenants')->insert([
            'name' => $name,
            'type' => 'personal',
            'status' => 'active',
        ]);

        return (int) $this->db->insertID();
    }

    protected function createProduct(int $tenantId, string $name): int
    {
        $this->db->table('products')->insert([
            'tenant_id' => $tenantId,
            'brand' => 'Mini GT',
            'name' => $name,
        ]);

        return (int) $this->db->insertID();
    }

    public function testTenantCannotReadAnotherTenantsProductByManipulatingItsId(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');
        $productB = $this->createProduct($tenantB, 'Produto privado B');
        $service = new CatalogService($this->db);

        $this->expectException(PageNotFoundException::class);

        $service->find($tenantA, $productB);
    }

    public function testTenantCannotEditAnotherTenantsProductByManipulatingItsId(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');
        $productB = $this->createProduct($tenantB, 'Produto privado B');
        $service = new CatalogService($this->db);

        try {
            $service->save($tenantA, ['name' => 'Tentativa de alteração'], $productB, null, []);
            self::fail('A alteração cross-tenant deveria ser recusada.');
        } catch (PageNotFoundException) {
            $product = $this->db->table('products')->where('id', $productB)->get()->getRowArray();

            self::assertSame('Produto privado B', $product['name']);
        }
    }
}
