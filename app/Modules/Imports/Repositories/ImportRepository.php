<?php

declare(strict_types=1);

namespace App\Modules\Imports\Repositories;

use CodeIgniter\Database\BaseConnection;

final class ImportRepository
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(int $tenantId): array
    {
        return $this->db->table('imports')->select('imports.*, products.name, products.brand, collection_items.id AS collection_id, (SELECT MAX(id) FROM product_images WHERE product_id = products.id) AS image_id', false)->join('products', 'products.id = imports.product_id')->join('collection_items', 'collection_items.import_id = imports.id', 'left')->where('imports.tenant_id', $tenantId)->orderBy('purchased_at', 'DESC')->get()->getResultArray();
    }

    /** @return array{products:list<array<string,mixed>>,brands:list<array<string,mixed>>} */
    public function formData(int $tenantId): array
    {
        return ['products' => $this->db->table('products')->where('tenant_id', $tenantId)->orderBy('brand')->orderBy('name')->get()->getResultArray(), 'brands' => $this->db->table('products')->select('brand')->where('tenant_id', $tenantId)->where('brand !=', '')->groupBy('brand')->orderBy('brand')->get()->getResultArray()];
    }

    /** @param array<string, mixed> $data */
    public function createProduct(array $data): int
    {
        $this->db->table('products')->insert($data);

        return (int) $this->db->insertID();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $this->db->table('imports')->insert($data);
    }

    /** @return array<string, mixed>|null */
    public function findForTenant(int $tenantId, int $id): ?array
    {
        return $this->db->table('imports')->where(['id' => $id, 'tenant_id' => $tenantId])->get()->getRowArray();
    }

    public function markReceived(int $tenantId, int $id, string $receivedAt, string $updatedAt): void
    {
        $this->db->table('imports')->where(['id' => $id, 'tenant_id' => $tenantId])->update(['status' => 'received', 'received_at' => $receivedAt, 'updated_at' => $updatedAt]);
    }

    /** @param array<string, mixed> $data */
    public function createCollectionItem(array $data): void
    {
        $this->db->table('collection_items')->insert($data);
    }
}
