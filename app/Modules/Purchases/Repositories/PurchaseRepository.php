<?php

declare(strict_types=1);

namespace App\Modules\Purchases\Repositories;

use CodeIgniter\Database\ConnectionInterface;

final class PurchaseRepository
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function all(int $tenantId): array
    {
        return $this->db->table('direct_purchases')->select('direct_purchases.*, collection_items.id AS collection_id, products.name AS product_name, products.brand, products.scale, (SELECT MAX(id) FROM product_images WHERE product_id = products.id) AS image_id', false)->join('products', 'products.id = direct_purchases.product_id')->join('collection_items', 'collection_items.direct_purchase_id = direct_purchases.id', 'left')->where('direct_purchases.tenant_id', $tenantId)->orderBy('purchased_at', 'DESC')->get()->getResultArray();
    }

    public function formData(int $tenantId): array
    {
        return ['products' => $this->db->table('products')->where('tenant_id', $tenantId)->orderBy('brand')->orderBy('name')->get()->getResultArray(), 'brands' => $this->db->table('products')->select('brand')->where('tenant_id', $tenantId)->where('brand !=', '')->groupBy('brand')->orderBy('brand')->get()->getResultArray()];
    }

    public function createProduct(array $data): int
    {
        $this->db->table('products')->insert($data);
        return (int) $this->db->insertID();
    }

    public function createPurchase(array $data): int
    {
        $this->db->table('direct_purchases')->insert($data);
        return (int) $this->db->insertID();
    }

    public function createCollectionItem(array $data): void
    {
        $this->db->table('collection_items')->insert($data);
    }
}
