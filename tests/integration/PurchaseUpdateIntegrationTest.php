<?php

declare(strict_types=1);

use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Purchases\Services\PurchaseService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class PurchaseUpdateIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    protected $refresh = false;

    /** @var list<int> */
    private array $tenantIds = [];

    /** @var list<string> */
    private array $storedImagePaths = [];

    public function testUpdateSynchronizesPurchaseAndCollectionItem(): void
    {
        $tenantId = $this->createTenant('Tenant compra edição');
        $originalProductId = $this->createProduct($tenantId, 'Modelo original');
        $replacementProductId = $this->createProduct($tenantId, 'Modelo corrigido');
        $purchaseId = $this->createPurchase($tenantId, $originalProductId);
        $collectionId = $this->createCollectionItem($tenantId, $purchaseId, $originalProductId);
        $service = new PurchaseService($this->db, new CatalogService($this->db));

        $service->update($tenantId, $purchaseId, [
            'product_id' => $replacementProductId,
            'new_product_name' => '',
            'store_name' => 'Loja corrigida',
            'purchased_at' => '2026-11-18',
            'quantity' => 2,
            'unit_price' => '125.50',
            'location' => 'Estante A',
            'notes' => 'Dados corrigidos',
        ], null);

        $purchase = $this->db->table('direct_purchases')->where('id', $purchaseId)->get()->getRowArray();
        $collection = $this->db->table('collection_items')->where('id', $collectionId)->get()->getRowArray();

        self::assertSame($replacementProductId, (int) $purchase['product_id']);
        self::assertSame('Loja corrigida', $purchase['store_name']);
        self::assertSame('2026-11-18', $purchase['purchased_at']);
        self::assertSame(2, (int) $purchase['quantity']);
        self::assertSame('125.50', $purchase['unit_price']);
        self::assertSame($replacementProductId, (int) $collection['product_id']);
        self::assertSame(2, (int) $collection['quantity']);
        self::assertSame('125.50', $collection['unit_cost']);
        self::assertSame('2026-11-18', $collection['received_at']);
        self::assertSame('Estante A', $collection['location']);
    }

    public function testUpdateDoesNotAllowAnotherTenantPurchase(): void
    {
        $tenantA = $this->createTenant('Tenant A compra edição');
        $tenantB = $this->createTenant('Tenant B compra edição');
        $productA = $this->createProduct($tenantA, 'Produto A');
        $productB = $this->createProduct($tenantB, 'Produto B');
        $purchaseB = $this->createPurchase($tenantB, $productB);
        $service = new PurchaseService($this->db, new CatalogService($this->db));

        $this->expectException(PageNotFoundException::class);

        $service->update($tenantA, $purchaseB, [
            'product_id' => $productA,
            'new_product_name' => '',
            'store_name' => 'Tentativa indevida',
            'purchased_at' => '2026-11-18',
            'quantity' => 1,
            'unit_price' => '10.00',
            'location' => '',
            'notes' => '',
        ], null);
    }

    public function testUpdateReplacesTheProductsPrimaryImage(): void
    {
        $tenantId = $this->createTenant('Tenant foto compra edição');
        $productId = $this->createProduct($tenantId, 'Miniatura com foto');
        $purchaseId = $this->createPurchase($tenantId, $productId);
        $this->createCollectionItem($tenantId, $purchaseId, $productId);
        $this->db->table('product_images')->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'path' => 'products/' . $tenantId . '/anterior.png',
            'mime_type' => 'image/png',
            'size_bytes' => 1,
            'sort_order' => 0,
            'is_primary' => 1,
        ]);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'purchase-image-');
        self::assertNotFalse($temporaryPath);
        file_put_contents(
            $temporaryPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLkzgAAAABJRU5ErkJggg==', true),
        );

        (new PurchaseService($this->db, new CatalogService($this->db)))->update(
            $tenantId,
            $purchaseId,
            $this->updatePayload($productId),
            $this->imageUpload($temporaryPath),
        );

        $primaryImage = $this->db->table('product_images')
            ->where(['tenant_id' => $tenantId, 'product_id' => $productId, 'is_primary' => 1])
            ->get()
            ->getRowArray();

        self::assertNotNull($primaryImage);
        self::assertNotSame('products/' . $tenantId . '/anterior.png', $primaryImage['path']);
        $uploadsDirectory = (new \Config\Paths())->writableDirectory . '/uploads/';
        $this->storedImagePaths[] = $uploadsDirectory . $primaryImage['path'];
        self::assertFileExists($this->storedImagePaths[0]);
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

    private function createProduct(int $tenantId, string $name): int
    {
        $this->db->table('products')->insert([
            'tenant_id' => $tenantId,
            'brand' => 'Mini GT',
            'name' => $name,
        ]);

        return (int) $this->db->insertID();
    }

    private function createPurchase(int $tenantId, int $productId): int
    {
        $this->db->table('direct_purchases')->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'store_name' => 'Loja original',
            'purchased_at' => '2026-11-01',
            'quantity' => 1,
            'unit_price' => '100.00',
        ]);

        return (int) $this->db->insertID();
    }

    private function createCollectionItem(int $tenantId, int $purchaseId, int $productId): int
    {
        $this->db->table('collection_items')->insert([
            'tenant_id' => $tenantId,
            'direct_purchase_id' => $purchaseId,
            'product_id' => $productId,
            'quantity' => 1,
            'unit_cost' => '100.00',
            'freight_amount' => '0.00',
            'received_at' => '2026-11-01',
        ]);

        return (int) $this->db->insertID();
    }

    /** @return array<string, string|int> */
    private function updatePayload(int $productId): array
    {
        return [
            'product_id' => $productId,
            'new_product_name' => '',
            'store_name' => 'Loja corrigida',
            'purchased_at' => '2026-11-18',
            'quantity' => 1,
            'unit_price' => '100.00',
            'location' => '',
            'notes' => '',
        ];
    }

    private function imageUpload(string $temporaryPath): UploadedFile
    {
        return new class ($temporaryPath, 'miniatura.png', 'image/png') extends UploadedFile {
            public function isValid(): bool
            {
                return ! $this->hasMoved && is_file($this->path);
            }

            public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
            {
                $name ??= 'miniatura.png';
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0700, true);
                }

                $destination = rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
                if (! rename($this->path, $destination)) {
                    throw new \RuntimeException('Não foi possível mover o arquivo de teste.');
                }

                $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'arquivo-temporario-inexistente';
                $this->name = $name;
                $this->hasMoved = true;

                return true;
            }
        };
    }

    protected function tearDown(): void
    {
        if ($this->tenantIds !== []) {
            $this->db->table('tenants')->whereIn('id', $this->tenantIds)->delete();
        }

        foreach ($this->storedImagePaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }
}
