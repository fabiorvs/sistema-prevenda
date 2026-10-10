<?php

namespace App\Modules\Sellers\Controllers;

use App\Controllers\BaseController;
use App\Modules\Sellers\Models\SellerModel;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;

class SellersController extends BaseController
{
    private function tenant(): int { return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id); }
    public function index(): string
    {
        $q = trim((string) $this->request->getGet('q')); $sellers = new SellerModel();
        if ($q !== '') $sellers->like('name', $q);
        return view('sellers/index', ['sellers' => $sellers->where('tenant_id', $this->tenant())->orderBy('name')->paginate(12), 'pager' => $sellers->pager, 'q' => $q]);
    }
    public function create(): string { return view('sellers/form', ['seller' => null]); }
    public function edit(int $id): string { return view('sellers/form', ['seller' => $this->owned($id)]); }
    public function store() { return $this->save(); }
    public function update(int $id) { return $this->save($this->owned($id)); }
    public function delete(int $id) { $seller = $this->owned($id); $this->removeImage($seller); (new SellerModel())->delete($seller['id']); return redirect()->to('/vendedores')->with('message', 'Vendedor excluído.'); }
    public function image(int $id)
    {
        $seller = $this->owned($id); $path = $seller['image_path'] ? WRITEPATH . 'uploads/' . $seller['image_path'] : null;
        if (! $path || ! is_file($path)) throw PageNotFoundException::forPageNotFound();
        return $this->response->setHeader('Content-Type', $seller['image_mime_type'])->setHeader('X-Content-Type-Options', 'nosniff')->setBody((string) file_get_contents($path));
    }
    private function save(?array $seller = null)
    {
        $rules = ['name' => 'required|max_length[160]', 'website' => 'permit_empty|valid_url', 'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $data = $this->request->getPost(['name', 'contact_name', 'whatsapp', 'instagram', 'website', 'notes']); $model = new SellerModel();
        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $tenant = $this->tenant(); $dir = WRITEPATH . 'uploads/sellers/' . $tenant;
            if (! is_dir($dir)) mkdir($dir, 0700, true);
            $filename = $file->getRandomName(); $mimeType = $file->getMimeType(); $file->move($dir, $filename);
            if ($seller) $this->removeImage($seller);
            $data['image_path'] = 'sellers/' . $tenant . '/' . $filename; $data['image_mime_type'] = $mimeType;
        }
        if ($seller) $model->update($seller['id'], $data); else $model->insert($data + ['tenant_id' => $this->tenant()]);
        return redirect()->to('/vendedores')->with('message', $seller ? 'Vendedor atualizado.' : 'Vendedor cadastrado.');
    }
    private function owned(int $id): array { $seller = (new SellerModel())->where(['id' => $id, 'tenant_id' => $this->tenant()])->first(); if (! $seller) throw PageNotFoundException::forPageNotFound(); return $seller; }
    private function removeImage(array $seller): void { if (! empty($seller['image_path'])) { $path = WRITEPATH . 'uploads/' . $seller['image_path']; if (is_file($path)) unlink($path); } }
}
