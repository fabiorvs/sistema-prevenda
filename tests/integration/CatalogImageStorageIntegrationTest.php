<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\ProductImageModel;
use App\Modules\Catalog\Services\CatalogService;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class CatalogImageStorageIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    public function testStoreImageReadsMetadataBeforeMovingTemporaryUpload(): void
    {
        $tenantId = $this->createTenant('Tenant imagens');
        $productId = $this->createProduct($tenantId);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'diecast-image-');
        self::assertNotFalse($temporaryPath);
        file_put_contents(
            $temporaryPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLkzgAAAABJRU5ErkJggg==', true),
        );

        $file = new class ($temporaryPath, 'miniatura.png', 'image/png') extends UploadedFile {
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

        (new CatalogService($this->db))->storeImage($tenantId, $productId, $file);

        $image = (new ProductImageModel())
            ->where(['tenant_id' => $tenantId, 'product_id' => $productId])
            ->first();

        self::assertNotNull($image);
        self::assertSame('image/png', $image['mime_type']);
        self::assertGreaterThan(0, (int) $image['size_bytes']);
        $uploadsDirectory = (new \Config\Paths())->writableDirectory . '/uploads/';
        self::assertFileExists($uploadsDirectory . $image['path']);

        unlink($uploadsDirectory . $image['path']);
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

    private function createProduct(int $tenantId): int
    {
        $this->db->table('products')->insert([
            'tenant_id' => $tenantId,
            'brand' => 'Mini GT',
            'name' => 'Miniatura de teste',
        ]);

        return (int) $this->db->insertID();
    }
}
