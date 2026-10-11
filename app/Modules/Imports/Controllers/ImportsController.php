<?php

declare(strict_types=1);

namespace App\Modules\Imports\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class ImportsController extends BaseController
{
    public function index(): string
    {
        $imports = db_connect()->table('imports')
            ->select('imports.*, products.name, products.brand, collection_items.id AS collection_id, (SELECT MAX(id) FROM product_images WHERE product_id = products.id) AS image_id', false)
            ->join('products', 'products.id = imports.product_id')
            ->join('collection_items', 'collection_items.import_id = imports.id', 'left')
            ->where('imports.tenant_id', $this->tenant())
            ->orderBy('purchased_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('imports/index', ['imports' => $imports]);
    }

    public function create(): string
    {
        $tenantId = $this->tenant();
        $db = db_connect();

        return view('imports/form', [
            'products' => $db->table('products')->where('tenant_id', $tenantId)->orderBy('brand')->orderBy('name')->get()->getResultArray(),
            'brands' => $db->table('products')->select('brand')->where('tenant_id', $tenantId)->where('brand !=', '')->groupBy('brand')->orderBy('brand')->get()->getResultArray(),
        ]);
    }

    public function store()
    {
        $rules = [
            'supplier_name' => 'required|max_length[160]',
            'purchased_at' => 'required|valid_date[Y-m-d]',
            'item_amount' => 'required|decimal|greater_than[0]',
            'exchange_rate' => 'required|decimal|greater_than[0]',
            'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $tenantId = $this->tenant();
        $db = db_connect();
        $productId = (int) $this->request->getPost('product_id');
        $newName = trim((string) $this->request->getPost('new_product_name'));

        if ($productId === 0 && $newName === '') {
            return redirect()->back()->withInput()->with('errors', ['new_product_name' => 'Informe o modelo da miniatura.']);
        }

        $now = gmdate('Y-m-d H:i:s');
        $db->transStart();

        if ($productId === 0) {
            $brand = trim((string) $this->request->getPost('new_product_brand'));
            if ($brand === '__new__') {
                $brand = trim((string) $this->request->getPost('custom_product_brand'));
            }

            $db->table('products')->insert([
                'tenant_id' => $tenantId,
                'brand' => $brand ?: 'Sem marca',
                'name' => $newName,
                'scale' => trim((string) $this->request->getPost('new_product_scale')) ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $productId = (int) $db->insertID();
            $this->saveImage($tenantId, $productId);
        }

        $db->table('imports')->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'supplier_name' => trim((string) $this->request->getPost('supplier_name')),
            'purchased_at' => $this->request->getPost('purchased_at'),
            'expected_at' => $this->request->getPost('expected_at') ?: null,
            'currency' => $this->request->getPost('currency') ?: 'USD',
            'exchange_rate' => $this->request->getPost('exchange_rate'),
            'item_amount' => $this->request->getPost('item_amount'),
            'shipping_amount' => $this->request->getPost('shipping_amount') ?: 0,
            'tax_amount' => $this->request->getPost('tax_amount') ?: 0,
            'tracking_code' => $this->request->getPost('tracking_code') ?: null,
            'carrier' => $this->request->getPost('carrier') ?: null,
            'status' => $this->request->getPost('status') ?: 'purchased',
            'location' => $this->request->getPost('location') ?: null,
            'notes' => $this->request->getPost('notes') ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $db->transComplete();
        if (! $db->transStatus()) {
            throw new \RuntimeException('Não foi possível cadastrar a importação.');
        }

        return redirect()->to('/importacoes')->with('message', 'Importação cadastrada.');
    }

    public function receive(int $id)
    {
        $db = db_connect();
        $item = $db->table('imports')->where(['id' => $id, 'tenant_id' => $this->tenant()])->get()->getRowArray();
        if (! $item) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if ($item['status'] === 'received') {
            return redirect()->to('/importacoes')->with('message', 'Esta importação já foi adicionada à coleção.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $date = date('Y-m-d');
        $db->transStart();
        $db->table('imports')->where('id', $id)->update([
            'status' => 'received',
            'received_at' => $date,
            'updated_at' => $now,
        ]);
        $db->table('collection_items')->insert([
            'tenant_id' => $item['tenant_id'],
            'import_id' => $id,
            'product_id' => $item['product_id'],
            'quantity' => 1,
            'unit_cost' => ((float) $item['item_amount'] + (float) $item['shipping_amount'] + (float) $item['tax_amount']) * (float) $item['exchange_rate'],
            'freight_amount' => 0,
            'received_at' => $date,
            'location' => $item['location'],
            'notes' => $item['notes'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new \RuntimeException('Não foi possível receber a importação.');
        }

        return redirect()->to('/importacoes')->with('message', 'Importação recebida e adicionada à coleção.');
    }

    private function saveImage(int $tenantId, int $productId): void
    {
        $file = $this->request->getFile('image');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return;
        }

        $directory = WRITEPATH . 'uploads/products/' . $tenantId;
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        $filename = $file->getRandomName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $file->move($directory, $filename);
        db_connect()->table('product_images')->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'path' => 'products/' . $tenantId . '/' . $filename,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'sort_order' => 0,
            'is_primary' => 1,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }
}
