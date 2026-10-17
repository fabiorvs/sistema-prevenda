<?php

declare(strict_types=1);

namespace App\Modules\Imports\Services;

use App\Modules\Catalog\Services\CatalogService;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;

final class ImportService
{
    public function __construct(private readonly ConnectionInterface $db, private readonly CatalogService $catalog)
    {
    }

    /** @return list<array<string,mixed>> */
    public function all(int $tenantId): array
    {
        return $this->db->table('imports')->select('imports.*, products.name, products.brand, collection_items.id AS collection_id, (SELECT MAX(id) FROM product_images WHERE product_id = products.id) AS image_id', false)->join('products', 'products.id = imports.product_id')->join('collection_items', 'collection_items.import_id = imports.id', 'left')->where('imports.tenant_id', $tenantId)->orderBy('purchased_at', 'DESC')->get()->getResultArray();
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
        if ($productId === 0 && trim((string) $data['new_product_name']) === '') throw new \InvalidArgumentException('Informe o modelo da miniatura.');
        $this->db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        if ($productId === 0) {
            $brand = trim((string) $data['new_product_brand']);
            if ($brand === '__new__') $brand = trim((string) $data['custom_product_brand']);
            $this->db->table('products')->insert(['tenant_id' => $tenantId, 'brand' => $brand ?: 'Sem marca', 'name' => trim((string) $data['new_product_name']), 'scale' => trim((string) $data['new_product_scale']) ?: null, 'created_at' => $now, 'updated_at' => $now]);
            $productId = (int) $this->db->insertID();
            if ($image && $image->isValid() && ! $image->hasMoved()) $this->catalog->storeImage($tenantId, $productId, $image);
        }
        $this->db->table('imports')->insert(['tenant_id' => $tenantId, 'product_id' => $productId, 'supplier_name' => trim((string) $data['supplier_name']), 'purchased_at' => $data['purchased_at'], 'expected_at' => $data['expected_at'] ?: null, 'currency' => $data['currency'] ?: 'USD', 'exchange_rate' => $data['exchange_rate'], 'item_amount' => $data['item_amount'], 'shipping_amount' => $data['shipping_amount'] ?: 0, 'tax_amount' => $data['tax_amount'] ?: 0, 'tracking_code' => $data['tracking_code'] ?: null, 'carrier' => $data['carrier'] ?: null, 'status' => $data['status'] ?: 'purchased', 'location' => $data['location'] ?: null, 'notes' => $data['notes'] ?: null, 'created_at' => $now, 'updated_at' => $now]);
        $this->db->transComplete();
        if (! $this->db->transStatus()) throw new \RuntimeException('Não foi possível cadastrar a importação.');
    }

    public function receive(int $tenantId, int $id): bool
    {
        $item = $this->db->table('imports')->where(['id' => $id, 'tenant_id' => $tenantId])->get()->getRowArray();
        if (! $item) throw PageNotFoundException::forPageNotFound();
        if ($item['status'] === 'received') return false;
        $now = gmdate('Y-m-d H:i:s'); $date = date('Y-m-d');
        $this->db->transStart();
        $this->db->table('imports')->where('id', $id)->update(['status' => 'received', 'received_at' => $date, 'updated_at' => $now]);
        $this->db->table('collection_items')->insert(['tenant_id' => $tenantId, 'import_id' => $id, 'product_id' => $item['product_id'], 'quantity' => 1, 'unit_cost' => ((float) $item['item_amount'] + (float) $item['shipping_amount'] + (float) $item['tax_amount']) * (float) $item['exchange_rate'], 'freight_amount' => 0, 'received_at' => $date, 'location' => $item['location'], 'notes' => $item['notes'], 'created_at' => $now, 'updated_at' => $now]);
        $this->db->transComplete();
        if (! $this->db->transStatus()) throw new \RuntimeException('Não foi possível receber a importação.');
        return true;
    }
}
