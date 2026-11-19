<?php

declare(strict_types=1);

use App\Modules\Finance\Repositories\InvestmentEntryRepository;
use App\Modules\Finance\Services\DirectPurchaseFinanceEntryFactory;
use App\Modules\Finance\Services\InvestmentEntryService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class InvestmentEntryServiceIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    /** @var list<int> */
    private array $tenantIds = [];

    public function testListsGarageFreightAndDirectPurchasesForTheSelectedPeriodAndTenant(): void
    {
        $tenantId = $this->createTenant('Tenant financeiro período');
        $otherTenantId = $this->createTenant('Tenant financeiro privado');
        $sellerId = $this->createSeller($tenantId, 'Vendedor período');
        $otherSellerId = $this->createSeller($otherTenantId, 'Vendedor privado');
        $productId = $this->createProduct($tenantId, 'Toyota Celica');
        $otherProductId = $this->createProduct($otherTenantId, 'Produto privado');

        $garageItemId = $this->createGarageItem($tenantId, $sellerId, $productId);
        $this->db->table('garage_payments')->insert([
            'tenant_id' => $tenantId,
            'garage_item_id' => $garageItemId,
            'amount' => '250.00',
            'method' => 'pix',
            'paid_at' => '2026-11-12 10:00:00',
        ]);
        $this->createShipment($tenantId, $sellerId, 'ENV-PERIODO', '36.00');
        $this->createDirectPurchase($tenantId, $productId, '130.80');

        $privateGarageItemId = $this->createGarageItem($otherTenantId, $otherSellerId, $otherProductId);
        $this->db->table('garage_payments')->insert([
            'tenant_id' => $otherTenantId,
            'garage_item_id' => $privateGarageItemId,
            'amount' => '999.00',
            'method' => 'pix',
            'paid_at' => '2026-11-12 10:00:00',
        ]);

        $entries = (new InvestmentEntryService(
            new InvestmentEntryRepository($this->db),
            new DirectPurchaseFinanceEntryFactory(),
        ))->listForPeriod($tenantId, '2026-11-01', '2026-11-19');

        self::assertCount(3, $entries);
        self::assertSame(['garage_payment', 'shipment', 'purchase'], array_column($entries, 'source'));
        self::assertSame(416.8, array_sum(array_column($entries, 'amount')));
        self::assertSame(['Pagamento da Garagem: Mini GT Toyota Celica', 'Frete do envio ENV-PERIODO', 'Compra: Mini GT Toyota Celica'], array_column($entries, 'description'));
        self::assertStringNotContainsString('privado', implode(' ', array_column($entries, 'description')));
    }

    private function createTenant(string $name): int
    {
        $this->db->table('tenants')->insert([
            'name' => $name,
            'type' => 'personal',
            'status' => 'active',
        ]);

        $tenantId = (int) $this->db->insertID();
        $this->tenantIds[] = $tenantId;

        return $tenantId;
    }

    private function createSeller(int $tenantId, string $name): int
    {
        $this->db->table('sellers')->insert([
            'tenant_id' => $tenantId,
            'name' => $name,
        ]);

        return (int) $this->db->insertID();
    }

    private function createProduct(int $tenantId, string $name): int
    {
        $this->db->table('products')->insert([
            'tenant_id' => $tenantId,
            'brand' => 'Mini GT',
            'name' => $name,
        ]);

        return (int) $this->db->insertID();
    }

    private function createGarageItem(int $tenantId, int $sellerId, int $productId): int
    {
        $this->db->table('garage_items')->insert([
            'tenant_id' => $tenantId,
            'seller_id' => $sellerId,
            'product_id' => $productId,
            'quantity' => 1,
            'unit_price' => '250.00',
            'acquired_at' => '2026-11-12',
            'status' => 'stored',
        ]);

        return (int) $this->db->insertID();
    }

    private function createShipment(int $tenantId, int $sellerId, string $reference, string $amount): void
    {
        $this->db->table('shipments')->insert([
            'tenant_id' => $tenantId,
            'seller_id' => $sellerId,
            'reference' => $reference,
            'carrier' => 'Transportadora período',
            'shipping_amount' => $amount,
            'status' => 'shipped',
            'shipped_at' => '2026-11-13',
        ]);
    }

    private function createDirectPurchase(int $tenantId, int $productId, string $amount): void
    {
        $this->db->table('direct_purchases')->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'store_name' => 'Loja período',
            'purchased_at' => '2026-11-18',
            'quantity' => 1,
            'unit_price' => $amount,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->tenantIds !== []) {
            $this->db->table('tenants')->whereIn('id', $this->tenantIds)->delete();
        }

        parent::tearDown();
    }
}
