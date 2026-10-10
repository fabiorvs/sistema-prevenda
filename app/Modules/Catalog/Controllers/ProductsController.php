<?php

namespace App\Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use App\Modules\Catalog\Models\ProductImageModel;
use App\Modules\Catalog\Models\ProductModel;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;

class ProductsController extends BaseController
{
    private function tenant(): int { return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id); }

    public function index(): string
    {
        $q = trim((string) $this->request->getGet('q')); $products = new ProductModel();
        if ($q !== '') $products->groupStart()->like('name', $q)->orLike('brand', $q)->groupEnd();
        $rows = $products->where('tenant_id', $this->tenant())->orderBy('name')->paginate(12); $images = new ProductImageModel();
        foreach ($rows as &$row) { $image = $images->where(['tenant_id' => $this->tenant(), 'product_id' => $row['id'], 'is_primary' => 1])->first(); $row['image_id'] = $image['id'] ?? null; }
        return view('products/index', ['products' => $rows, 'pager' => $products->pager, 'q' => $q]);
    }
    public function create(): string { return view('products/form', ['product' => null]); }
    public function edit(int $id): string { return view('products/form', ['product' => $this->owned($id)]); }
    public function store() { return $this->save(); }
    public function update(int $id) { return $this->save($this->owned($id)); }
    public function delete(int $id) { (new ProductModel())->delete($this->owned($id)['id']); return redirect()->to('/carrinhos')->with('message', 'Carrinho excluído.'); }
    public function image(int $id)
    {
        $row = (new ProductImageModel())->where(['id' => $id, 'tenant_id' => $this->tenant()])->first(); if (! $row) throw PageNotFoundException::forPageNotFound();
        $path = WRITEPATH . 'uploads/' . $row['path']; if (! is_file($path)) throw PageNotFoundException::forPageNotFound();
        return $this->response->setHeader('Content-Type', $row['mime_type'])->setHeader('X-Content-Type-Options', 'nosniff')->setBody((string) file_get_contents($path));
    }
    private function save(?array $product = null)
    {
        $rules = ['brand' => 'required|max_length[100]', 'name' => 'required|max_length[160]', 'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $model = new ProductModel(); $data = $this->request->getPost(['brand', 'name', 'manufacturer_code', 'scale', 'series', 'notes']);
        if ($product) { $model->update($product['id'], $data); $id = (int) $product['id']; } else { $model->insert($data + ['tenant_id' => $this->tenant()]); $id = (int) $model->getInsertID(); }
        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && ! $file->hasMoved()) { $tenant = $this->tenant(); $dir = WRITEPATH . 'uploads/products/' . $tenant; if (! is_dir($dir)) mkdir($dir, 0700, true); $name = $file->getRandomName(); $mimeType = $file->getMimeType(); $size = $file->getSize(); $file->move($dir, $name); $images = new ProductImageModel(); $images->where(['tenant_id' => $tenant, 'product_id' => $id])->set('is_primary', 0)->update(); $images->insert(['tenant_id' => $tenant, 'product_id' => $id, 'path' => 'products/' . $tenant . '/' . $name, 'mime_type' => $mimeType, 'size_bytes' => $size, 'sort_order' => 0, 'is_primary' => 1]); }
        return redirect()->to('/carrinhos')->with('message', $product ? 'Carrinho atualizado.' : 'Carrinho cadastrado.');
    }
    private function owned(int $id): array { $product = (new ProductModel())->where(['id' => $id, 'tenant_id' => $this->tenant()])->first(); if (! $product) throw PageNotFoundException::forPageNotFound(); return $product; }
}
