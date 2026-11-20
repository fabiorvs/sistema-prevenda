<?php

declare(strict_types=1);

use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Garage\Models\GarageItemModel;
use App\Modules\Garage\Models\GaragePaymentModel;
use App\Modules\Garage\Services\GarageItemService;
use App\Modules\Sellers\Models\SellerModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class GarageItemUpdateIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    /** @var list<int> */
    private array $tenantIds = [];

    /** @var list<string> */
    private array $storedImagePaths = [];

    public function testUpdateReplacesTheGarageItemsPrimaryImage(): void
    {
        $tenantId = $this->createTenant('Tenant garagem edição');
        $sellerId = $this->createSeller($tenantId, 'Vendedor da garagem');
        $productId = $this->createProduct($tenantId, 'Modelo da garagem');
        $garageItemId = $this->createGarageItem($tenantId, $sellerId, $productId);
        $oldImagePath = 'products/' . $tenantId . '/foto-anterior.png';
        $this->db->table('product_images')->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'path' => $oldImagePath,
            'mime_type' => 'image/png',
            'size_bytes' => 1,
            'sort_order' => 0,
            'is_primary' => 1,
        ]);
        $this->createStoredImage($oldImagePath);
        $temporaryPath = $this->createTemporaryImage();

        $this->service()->update($tenantId, $garageItemId, [
            'seller_id' => $sellerId,
            'unit_price' => '125.50',
            'acquired_at' => '2026-11-19',
            'next_payment_date' => '2026-12-10',
            'notes' => 'Dados e foto corrigidos',
        ], $this->imageUpload($temporaryPath));

        $item = $this->db->table('garage_items')->where('id', $garageItemId)->get()->getRowArray();
        $primaryImage = $this->db->table('product_images')
            ->where(['tenant_id' => $tenantId, 'product_id' => $productId, 'is_primary' => 1])
            ->get()
            ->getRowArray();

        self::assertSame('125.50', $item['unit_price']);
        self::assertSame('2026-11-19', $item['acquired_at']);
        self::assertSame('2026-12-10', $item['next_payment_date']);
        self::assertSame('Dados e foto corrigidos', $item['notes']);
        self::assertNotNull($primaryImage);
        self::assertNotSame($oldImagePath, $primaryImage['path']);
        self::assertSame(1, (int) $primaryImage['is_primary']);
        $this->storedImagePaths[] = WRITEPATH . 'uploads/' . $primaryImage['path'];
        self::assertFileExists($this->storedImagePaths[1]);
    }

    private function service(): GarageItemService
    {
        return new GarageItemService(
            new GarageItemModel(),
            new GaragePaymentModel(),
            new SellerModel(),
            new CatalogService($this->db),
        );
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
            'unit_price' => '100.00',
            'acquired_at' => '2026-11-01',
            'status' => 'stored',
        ]);

        return (int) $this->db->insertID();
    }

    private function createStoredImage(string $relativePath): void
    {
        $path = WRITEPATH . 'uploads/' . $relativePath;
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        file_put_contents($path, 'imagem anterior');
        $this->storedImagePaths[] = $path;
    }

    private function createTemporaryImage(): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'garage-image-');
        self::assertNotFalse($temporaryPath);
        file_put_contents(
            $temporaryPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLkzgAAAABJRU5ErkJggg==', true),
        );

        return $temporaryPath;
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
