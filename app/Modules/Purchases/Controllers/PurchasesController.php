<?php

declare(strict_types=1);

namespace App\Modules\Purchases\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class PurchasesController extends BaseController
{
    public function index(): string
    {
        $tenantId = $this->tenant(); $db = db_connect();
        $purchases = $db->table('direct_purchases')->select('direct_purchases.*, collection_items.id AS collection_id, products.name AS product_name, products.brand, products.scale, (SELECT MAX(id) FROM product_images WHERE product_id = products.id) AS image_id', false)->join('products', 'products.id = direct_purchases.product_id')->join('collection_items', 'collection_items.direct_purchase_id = direct_purchases.id', 'left')->where('direct_purchases.tenant_id', $tenantId)->orderBy('purchased_at', 'DESC')->get()->getResultArray();
        return view('purchases/index', ['purchases' => $purchases]);
    }
    public function create(): string
    {
        $db = db_connect(); $tenantId = $this->tenant();
        return view('purchases/form', ['products' => $db->table('products')->where('tenant_id', $tenantId)->orderBy('brand')->orderBy('name')->get()->getResultArray(), 'brands' => $db->table('products')->select('brand')->where('tenant_id', $tenantId)->where('brand !=', '')->groupBy('brand')->orderBy('brand')->get()->getResultArray()]);
    }
    public function store()
    {
        $rules = ['store_name' => 'required|max_length[160]', 'purchased_at' => 'required|valid_date[Y-m-d]', 'quantity' => 'required|is_natural_no_zero', 'unit_price' => 'required|decimal|greater_than[0]', 'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $tenantId = $this->tenant(); $db = db_connect(); $productId = (int) $this->request->getPost('product_id'); $name = trim((string) $this->request->getPost('new_product_name'));
        if ($productId === 0 && $name === '') return redirect()->back()->withInput()->with('errors', ['new_product_name' => 'Informe o modelo da miniatura.']);
        $now = gmdate('Y-m-d H:i:s'); $db->transStart();
        $brand = trim((string) $this->request->getPost('new_product_brand'));
        if ($brand === '__new__') $brand = trim((string) $this->request->getPost('custom_product_brand'));
        if ($productId === 0) { $db->table('products')->insert(['tenant_id' => $tenantId, 'brand' => $brand ?: 'Sem marca', 'name' => $name, 'scale' => trim((string) $this->request->getPost('new_product_scale')) ?: null, 'created_at' => $now, 'updated_at' => $now]); $productId = (int) $db->insertID(); $this->saveImage($tenantId, $productId); }
        $db->table('direct_purchases')->insert(['tenant_id' => $tenantId, 'product_id' => $productId, 'store_name' => trim((string) $this->request->getPost('store_name')), 'purchased_at' => $this->request->getPost('purchased_at'), 'quantity' => (int) $this->request->getPost('quantity'), 'unit_price' => number_format((float) $this->request->getPost('unit_price'), 2, '.', ''), 'location' => trim((string) $this->request->getPost('location')) ?: null, 'notes' => trim((string) $this->request->getPost('notes')) ?: null, 'created_at' => $now, 'updated_at' => $now]);
        $purchaseId = (int) $db->insertID(); $db->table('collection_items')->insert(['tenant_id' => $tenantId, 'direct_purchase_id' => $purchaseId, 'product_id' => $productId, 'quantity' => (int) $this->request->getPost('quantity'), 'unit_cost' => number_format((float) $this->request->getPost('unit_price'), 2, '.', ''), 'freight_amount' => 0, 'received_at' => $this->request->getPost('purchased_at'), 'location' => trim((string) $this->request->getPost('location')) ?: null, 'notes' => trim((string) $this->request->getPost('notes')) ?: null, 'created_at' => $now, 'updated_at' => $now]);
        $db->transComplete(); if (! $db->transStatus()) throw new \RuntimeException('Não foi possível registrar a compra.');
        return redirect()->to('/compras')->with('message', 'Compra registrada e adicionada à coleção.');
    }
    private function saveImage(int $tenantId, int $productId): void { $file = $this->request->getFile('image'); if (! $file || ! $file->isValid() || $file->hasMoved()) return; $mimeType = $file->getMimeType(); $size = $file->getSize(); $dir = WRITEPATH . 'uploads/products/' . $tenantId; if (! is_dir($dir)) mkdir($dir, 0700, true); $name = $file->getRandomName(); $file->move($dir, $name); $db = db_connect(); $db->table('product_images')->insert(['tenant_id' => $tenantId, 'product_id' => $productId, 'path' => 'products/' . $tenantId . '/' . $name, 'mime_type' => $mimeType, 'size_bytes' => $size, 'sort_order' => 0, 'is_primary' => 1, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')]); }
    private function tenant(): int { return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id); }
}
