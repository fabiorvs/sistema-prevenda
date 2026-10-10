<?php

declare(strict_types=1);

namespace App\Modules\Collection\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class CollectionController extends BaseController
{
    public function index(): string
    {
        $tenantId = $this->tenant();
        $filters = ['q' => trim((string) $this->request->getGet('q')), 'brand' => trim((string) $this->request->getGet('brand')), 'scale' => trim((string) $this->request->getGet('scale')), 'seller_id' => (int) $this->request->getGet('seller_id'), 'location' => (string) $this->request->getGet('location')];
        $query = db_connect()->table('collection_items')
            ->select('collection_items.*, products.name AS product_name, products.brand, products.scale, COALESCE(sellers.name, direct_purchases.store_name) AS seller_name, preorders.id AS preorder_id, garage_items.id AS garage_item_id, direct_purchases.id AS direct_purchase_id, (SELECT MAX(id) FROM product_images WHERE product_id = products.id) AS image_id', false)
            ->join('products', 'products.id = collection_items.product_id')->join('shipment_items', 'shipment_items.id = collection_items.shipment_item_id', 'left')->join('shipments', 'shipments.id = shipment_items.shipment_id', 'left')->join('sellers', 'sellers.id = shipments.seller_id', 'left')->join('direct_purchases', 'direct_purchases.id = collection_items.direct_purchase_id', 'left')->where('collection_items.tenant_id', $tenantId);
        if ($filters['q'] !== '') $query->groupStart()->like('products.name', $filters['q'])->orLike('products.brand', $filters['q'])->orLike('sellers.name', $filters['q'])->groupEnd();
        if ($filters['brand'] !== '') $query->where('products.brand', $filters['brand']);
        if ($filters['scale'] !== '') $query->where('products.scale', $filters['scale']);
        if ($filters['seller_id'] > 0) $query->where('shipments.seller_id', $filters['seller_id']);
        if ($filters['location'] === 'set') $query->where('collection_items.location IS NOT NULL', null, false)->where('collection_items.location !=', '');
        if ($filters['location'] === 'unset') $query->groupStart()->where('collection_items.location IS NULL', null, false)->orWhere('collection_items.location', '')->groupEnd();
        $items = $query->orderBy('collection_items.received_at', 'DESC')->get()->getResultArray();
        $productIds = array_values(array_unique(array_map(static fn (array $item): int => (int) $item['product_id'], $items)));
        $imagesByProduct = [];
        if ($productIds !== []) {
            foreach (db_connect()->table('product_images')->where('tenant_id', $tenantId)->whereIn('product_id', $productIds)->orderBy('sort_order')->get()->getResultArray() as $image) {
                $imagesByProduct[(int) $image['product_id']][] = $image;
            }
        }
        foreach ($items as &$item) { $item['images'] = $imagesByProduct[(int) $item['product_id']] ?? []; $item['source'] = ! empty($item['direct_purchase_id']) ? 'Compra presencial' : (! empty($item['garage_item_id']) ? 'Garagem' : 'Pré-venda'); }
        unset($item);
        $summary = ['items' => 0, 'invested' => 0.0, 'brands' => [], 'scales' => [], 'sellers' => []];
        foreach ($items as $item) {
            $summary['items'] += (int) $item['quantity']; $summary['invested'] += (int) $item['quantity'] * (float) $item['unit_cost'] + (float) $item['freight_amount'];
            $summary['brands'][$item['brand']] = ($summary['brands'][$item['brand']] ?? 0) + (int) $item['quantity'];
            $summary['scales'][$item['scale'] ?: 'Sem escala'] = ($summary['scales'][$item['scale'] ?: 'Sem escala'] ?? 0) + (int) $item['quantity'];
            $summary['sellers'][$item['seller_name']] = ($summary['sellers'][$item['seller_name']] ?? 0) + (int) $item['quantity'];
        }
        arsort($summary['brands']); arsort($summary['scales']); arsort($summary['sellers']);
        $db = db_connect();
        return view('collection/index', ['items' => $items, 'summary' => $summary, 'filters' => $filters, 'brands' => $db->table('products')->select('brand')->where('tenant_id', $tenantId)->where('brand !=', '')->groupBy('brand')->orderBy('brand')->get()->getResultArray(), 'scales' => $db->table('products')->select('scale')->where('tenant_id', $tenantId)->where('scale IS NOT NULL', null, false)->where('scale !=', '')->groupBy('scale')->orderBy('scale')->get()->getResultArray(), 'sellers' => $db->table('sellers')->where('tenant_id', $tenantId)->orderBy('name')->get()->getResultArray()]);
    }

    public function show(int $id): string
    {
        $tenantId = $this->tenant(); $db = db_connect();
        $item = $db->table('collection_items')->select('collection_items.*, products.name AS product_name, products.brand, products.scale, COALESCE(sellers.name, direct_purchases.store_name) AS seller_name, preorders.reference, garage_items.id AS garage_item_id, direct_purchases.id AS direct_purchase_id')->join('products', 'products.id = collection_items.product_id')->join('shipment_items', 'shipment_items.id = collection_items.shipment_item_id', 'left')->join('shipments', 'shipments.id = shipment_items.shipment_id', 'left')->join('sellers', 'sellers.id = shipments.seller_id', 'left')->join('preorder_items', 'preorder_items.id = shipment_items.preorder_item_id', 'left')->join('preorders', 'preorders.id = preorder_items.preorder_id', 'left')->join('garage_items', 'garage_items.id = shipment_items.garage_item_id', 'left')->join('direct_purchases', 'direct_purchases.id = collection_items.direct_purchase_id', 'left')->where(['collection_items.id' => $id, 'collection_items.tenant_id' => $tenantId])->get()->getRowArray();
        if (! $item) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $item['source'] = ! empty($item['direct_purchase_id']) ? 'Compra presencial' : (! empty($item['garage_item_id']) ? 'Garagem' : 'Pré-venda');
        $images = $db->table('product_images')->where(['tenant_id' => $tenantId, 'product_id' => $item['product_id']])->orderBy('sort_order')->get()->getResultArray();
        return view('collection/show', ['item' => $item, 'images' => $images]);
    }

    public function updateLocation(int $id)
    {
        if (! $this->validate(['location' => 'permit_empty|max_length[120]', 'notes' => 'permit_empty|max_length[2000]'])) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $updated = db_connect()->table('collection_items')->where(['id' => $id, 'tenant_id' => $this->tenant()])->update(['location' => trim((string) $this->request->getPost('location')) ?: null, 'notes' => trim((string) $this->request->getPost('notes')) ?: null, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        if (! $updated) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        return redirect()->to('/colecao')->with('message', 'Localização da miniatura atualizada.');
    }

    public function addPhoto(int $id)
    {
        if (! $this->validate(['photo' => 'uploaded[photo]|max_size[photo,5120]|is_image[photo]|mime_in[photo,image/jpeg,image/png,image/webp]'])) return redirect()->back()->with('errors', $this->validator->getErrors());
        $tenantId = $this->tenant();
        $item = db_connect()->table('collection_items')->where(['id' => $id, 'tenant_id' => $tenantId])->get()->getRowArray();
        if (! $item) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $file = $this->request->getFile('photo'); $directory = WRITEPATH . 'uploads/products/' . $tenantId;
        if (! is_dir($directory)) mkdir($directory, 0700, true);
        $filename = $file->getRandomName(); $file->move($directory, $filename);
        db_connect()->table('product_images')->insert(['tenant_id' => $tenantId, 'product_id' => $item['product_id'], 'path' => 'products/' . $tenantId . '/' . $filename, 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'sort_order' => 99, 'is_primary' => 0, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')]);
        return redirect()->to('/colecao')->with('message', 'Foto adicionada à galeria da miniatura.');
    }

    private function tenant(): int { return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id); }
}
