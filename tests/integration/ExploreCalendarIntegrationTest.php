<?php

declare(strict_types=1);

use App\Modules\Explore\Repositories\ExploreRepository;
use App\Modules\Explore\Services\ExploreService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ExploreCalendarIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    public function testCalendarIncludesPurchasesAndReservationsOnlyForActiveTenant(): void
    {
        $tenantA = $this->createTenant('Tenant calendário A');
        $tenantB = $this->createTenant('Tenant calendário B');
        $productA = $this->createProduct($tenantA, 'Nissan Skyline GT-R');
        $productB = $this->createProduct($tenantB, 'Produto privado');
        $sellerA = $this->createSeller($tenantA, 'Loja A');
        $sellerB = $this->createSeller($tenantB, 'Loja B');

        $this->db->table('direct_purchases')->insert([
            'tenant_id' => $tenantA,
            'product_id' => $productA,
            'store_name' => 'Evento Diecast',
            'purchased_at' => '2026-10-05',
            'quantity' => 1,
            'unit_price' => '75.00',
        ]);
        $this->db->table('garage_items')->insert([
            'tenant_id' => $tenantA,
            'seller_id' => $sellerA,
            'product_id' => $productA,
            'acquired_at' => '2026-10-06',
            'quantity' => 1,
            'unit_price' => '100.00',
            'status' => 'stored',
        ]);
        $this->db->table('imports')->insert([
            'tenant_id' => $tenantA,
            'product_id' => $productA,
            'supplier_name' => 'Loja internacional',
            'purchased_at' => '2026-10-07',
            'currency' => 'USD',
            'exchange_rate' => '5.00',
            'item_amount' => '20.00',
            'shipping_amount' => '5.00',
            'tax_amount' => '0.00',
            'status' => 'purchased',
        ]);
        $this->db->table('preorders')->insert([
            'tenant_id' => $tenantA,
            'seller_id' => $sellerA,
            'reference' => 'PV-CAL-A',
            'order_date' => '2026-10-08',
            'status' => 'reserved',
        ]);
        $this->db->table('direct_purchases')->insert([
            'tenant_id' => $tenantB,
            'product_id' => $productB,
            'store_name' => 'Compra privada',
            'purchased_at' => '2026-10-09',
            'quantity' => 1,
            'unit_price' => '90.00',
        ]);
        $this->db->table('preorders')->insert([
            'tenant_id' => $tenantB,
            'seller_id' => $sellerB,
            'reference' => 'PV-CAL-B',
            'order_date' => '2026-10-09',
            'status' => 'reserved',
        ]);

        $service = new ExploreService(new ExploreRepository($this->db));
        $events = $service->calendarEvents($tenantA, '2026-10');

        self::assertCount(4, $events);
        self::assertSame(['Compra', 'Compra', 'Compra', 'Reserva'], array_column($events, 'type'));
        self::assertSame(['2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08'], array_column($events, 'date'));
        self::assertStringContainsString('Evento Diecast', $events[0]['title']);
        self::assertStringContainsString('PV-CAL-A', $events[3]['title']);
        self::assertStringNotContainsString('privada', implode(' ', array_column($events, 'title')));
        self::assertStringNotContainsString('PV-CAL-B', implode(' ', array_column($events, 'title')));
    }

    private function createTenant(string $name): int
    {
        $this->db->table('tenants')->insert([
            'name' => $name,
            'type' => 'personal',
            'status' => 'active',
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

    private function createSeller(int $tenantId, string $name): int
    {
        $this->db->table('sellers')->insert([
            'tenant_id' => $tenantId,
            'name' => $name,
        ]);

        return (int) $this->db->insertID();
    }
}
