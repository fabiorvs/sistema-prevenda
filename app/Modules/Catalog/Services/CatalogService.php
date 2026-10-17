<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\ProductImageModel;
use App\Modules\Catalog\Models\ProductModel;
use App\Modules\Catalog\Repositories\CatalogRepository;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;

/** Operações de domínio do catálogo, incluindo a gestão do acervo de imagens. */
final class CatalogService
{
    private readonly CatalogRepository $catalog;

    public function __construct(ConnectionInterface $db)
    {
        $this->catalog = new CatalogRepository($db);
    }

    /** @return array{products: list<array<string,mixed>>, pager: mixed, filters: array<string,string>, brands: list<array<string,mixed>>, scales: list<array<string,mixed>>} */
    public function paginated(int $tenantId, array $filters): array
    {
        $products = new ProductModel();
        $products->where('tenant_id', $tenantId);
        if ($filters['q'] !== '') {
            $products->groupStart()->like('name', $filters['q'])->orLike('brand', $filters['q'])->orLike('manufacturer_code', $filters['q'])->groupEnd();
        }
        if ($filters['brand'] !== '') {
            $products->where('brand', $filters['brand']);
        }
        if ($filters['scale'] !== '') {
            $products->where('scale', $filters['scale']);
        }

        $rows = $products->orderBy('brand')->orderBy('name')->paginate(12);
        $products->pager->only(['q', 'brand', 'scale']);
        $images = new ProductImageModel();
        foreach ($rows as &$row) {
            $image = $images->where(['tenant_id' => $tenantId, 'product_id' => $row['id'], 'is_primary' => 1])->first();
            $row['image_id'] = $image['id'] ?? null;
        }
        unset($row);

        return [
            'products' => $rows,
            'pager' => $products->pager,
            'filters' => $filters,
            'brands' => $this->catalog->brands($tenantId),
            'scales' => $this->catalog->scales($tenantId),
        ];
    }

    /** @return array<string,mixed> */
    public function find(int $tenantId, int $id): array
    {
        $product = (new ProductModel())->where(['id' => $id, 'tenant_id' => $tenantId])->first();
        if ($product === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $product;
    }

    /** @return list<array<string,mixed>> */
    public function images(int $tenantId, int $productId): array
    {
        return (new ProductImageModel())->where(['tenant_id' => $tenantId, 'product_id' => $productId])->orderBy('is_primary', 'DESC')->orderBy('sort_order')->get()->getResultArray();
    }

    /** @param array<string,mixed> $data @param list<UploadedFile> $gallery */
    public function save(int $tenantId, array $data, ?int $productId, ?UploadedFile $primary, array $gallery): int
    {
        $model = new ProductModel();
        if ($productId !== null) {
            $this->find($tenantId, $productId);
            $model->update($productId, $data);
        } else {
            $model->insert($data + ['tenant_id' => $tenantId]);
            $productId = (int) $model->getInsertID();
        }

        if ($primary instanceof UploadedFile && $primary->isValid() && ! $primary->hasMoved()) {
            (new ProductImageModel())->where(['tenant_id' => $tenantId, 'product_id' => $productId])->set('is_primary', 0)->update();
            $this->storeImage($tenantId, $productId, $primary, true);
        }
        foreach ($gallery as $file) {
            if ($file->getError() !== UPLOAD_ERR_NO_FILE) {
                $this->storeImage($tenantId, $productId, $file, false);
            }
        }

        return $productId;
    }

    public function delete(int $tenantId, int $productId): void
    {
        $this->find($tenantId, $productId);
        foreach ($this->images($tenantId, $productId) as $image) {
            $this->deleteImageFile($image);
        }
        $this->catalog->deleteImagesForProduct($tenantId, $productId);
        (new ProductModel())->delete($productId);
    }

    public function deleteImage(int $tenantId, int $productId, int $imageId): void
    {
        $this->find($tenantId, $productId);
        $images = new ProductImageModel();
        $image = $images->where(['id' => $imageId, 'tenant_id' => $tenantId, 'product_id' => $productId])->first();
        if ($image === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        $wasPrimary = (int) $image['is_primary'] === 1;
        $this->deleteImageFile($image);
        $images->delete($imageId);
        if ($wasPrimary && ($replacement = $images->where(['tenant_id' => $tenantId, 'product_id' => $productId])->orderBy('sort_order')->first())) {
            $images->update($replacement['id'], ['is_primary' => 1]);
        }
    }

    /** @return array<string,mixed> */
    public function image(int $tenantId, int $imageId): array
    {
        $image = (new ProductImageModel())->where(['id' => $imageId, 'tenant_id' => $tenantId])->first();
        if ($image === null || ! is_file(WRITEPATH . 'uploads/' . $image['path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $image;
    }

    public function validateGallery(array $gallery): ?string
    {
        foreach ($gallery as $file) {
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024 || ! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
                return 'Cada foto da galeria deve ser JPEG, PNG ou WebP válida, com no máximo 5 MB.';
            }
        }
        return null;
    }

    public function storeImage(int $tenantId, int $productId, UploadedFile $file, bool $primary = true): void
    {
        $directory = WRITEPATH . 'uploads/products/' . $tenantId;
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $filename = $file->getRandomName();
        $file->move($directory, $filename);
        $max = (new ProductImageModel())->selectMax('sort_order')->where(['tenant_id' => $tenantId, 'product_id' => $productId])->first();
        (new ProductImageModel())->insert(['tenant_id' => $tenantId, 'product_id' => $productId, 'path' => 'products/' . $tenantId . '/' . $filename, 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'sort_order' => ((int) ($max['sort_order'] ?? -1)) + 1, 'is_primary' => $primary ? 1 : 0]);
    }

    /** @param array<string,mixed> $image */
    private function deleteImageFile(array $image): void
    {
        $path = WRITEPATH . 'uploads/' . $image['path'];
        if (is_file($path)) {
            unlink($path);
        }
    }
}
