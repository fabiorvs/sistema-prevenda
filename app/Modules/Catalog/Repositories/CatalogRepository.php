<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Repositories;

use CodeIgniter\Database\ConnectionInterface;

final class CatalogRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function brands(int $tenantId): array
    {
        return $this->db->table('products')->select('brand')->where('tenant_id', $tenantId)->where('brand !=', '')->groupBy('brand')->orderBy('brand')->get()->getResultArray();
    }

    public function scales(int $tenantId): array
    {
        return $this->db->table('products')->select('scale')->where('tenant_id', $tenantId)->where('scale IS NOT NULL', null, false)->where('scale !=', '')->groupBy('scale')->orderBy('scale')->get()->getResultArray();
    }

    public function deleteImagesForProduct(int $tenantId, int $productId): void
    {
        $this->db->table('product_images')->where(['tenant_id' => $tenantId, 'product_id' => $productId])->delete();
    }
}
