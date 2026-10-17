<?php

declare(strict_types=1);

namespace App\Modules\Purchases\Services;

use App\Modules\Catalog\Services\CatalogService;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\Files\UploadedFile;

final class PurchaseService
{
    public function __construct(private readonly ConnectionInterface $db, private readonly CatalogService $catalog)
    {
    }

    /** @return list<array<string,mixed>> */
    public function all(int $tenantId): array
    {
        return $this->db->table('direct_purchases')->select('direct_purchases.*, collection_items.id AS collection_id, products.name AS product_name, products.brand, products.scale, (SELECT MAX(id) FROM product_images WHERE product_id = products.id) AS image_id', false)->join('products', 'products.id = direct_purchases.product_id')->join('collection_items', 'collection_items.direct_purchase_id = direct_purchases.id', 'left')->where('direct_purchases.tenant_id', $tenantId)->orderBy('purchased_at', 'DESC')->get()->getResultArray();
    }

    /** @return array{products:list<array<string,mixed>>,brands:list<array<string,mixed>>} */
    public function formData(int $tenantId): array
    {
        return ['products' => $this->db->table('products')->where('tenant_id', $tenantId)->orderBy('brand')->orderBy('name')->get()->getResultArray(), 'brands' => $this->db->table('products')->select('brand')->where('tenant_id', $tenantId)->where('brand !=', '')->groupBy('brand')->orderBy('brand')->get()->getResultArray()];
    }

    /** @param array<string,mixed> $data */
    public function register(int $tenantId, array $data, ?UploadedFile $image): void
    {
        $productId = (int) $data['product_id'];
        $name = trim((string) $data['new_product_name']);
        if ($productId === 0 && $name === '') {
            throw new \InvalidArgumentException('Informe o modelo da miniatura.');
        }
        $this->db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        if ($productId === 0) {
            $brand = trim((string) $data['new_product_brand']);
            if ($brand === '__new__') {
                $brand = trim((string) $data['custom_product_brand']);
            }
            $this->db->table('products')->insert(['tenant_id' => $tenantId, 'brand' => $brand ?: 'Sem marca', 'name' => $name, 'scale' => trim((string) $data['new_product_scale']) ?: null, 'created_at' => $now, 'updated_at' => $now]);
            $productId = (int) $this->db->insertID();
            if ($image && $image->isValid() && ! $image->hasMoved()) {
                $this->catalog->storeImage($tenantId, $productId, $image);
            }
        }
        $quantity = (int) $data['quantity'];
        $price = number_format((float) $data['unit_price'], 2, '.', '');
        $location = trim((string) $data['location']) ?: null;
        $notes = trim((string) $data['notes']) ?: null;
        $this->db->table('direct_purchases')->insert(['tenant_id' => $tenantId, 'product_id' => $productId, 'store_name' => trim((string) $data['store_name']), 'purchased_at' => $data['purchased_at'], 'quantity' => $quantity, 'unit_price' => $price, 'location' => $location, 'notes' => $notes, 'created_at' => $now, 'updated_at' => $now]);
        $purchaseId = (int) $this->db->insertID();
        $this->db->table('collection_items')->insert(['tenant_id' => $tenantId, 'direct_purchase_id' => $purchaseId, 'product_id' => $productId, 'quantity' => $quantity, 'unit_cost' => $price, 'freight_amount' => 0, 'received_at' => $data['purchased_at'], 'location' => $location, 'notes' => $notes, 'created_at' => $now, 'updated_at' => $now]);
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Não foi possível registrar a compra.');
        }
    }
}
