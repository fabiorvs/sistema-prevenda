<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use App\Modules\Catalog\Models\ProductImageModel;
use App\Modules\Catalog\Models\ProductModel;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;

final class ProductsController extends BaseController
{
    public function index(): string
    {
        $tenantId = $this->tenant();
        $filters = [
            'q' => trim((string) $this->request->getGet('q')),
            'brand' => trim((string) $this->request->getGet('brand')),
            'scale' => trim((string) $this->request->getGet('scale')),
        ];
        $products = new ProductModel();
        $products->where('tenant_id', $tenantId);
        if ($filters['q'] !== '') {
            $products->groupStart()->like('name', $filters['q'])->orLike('brand', $filters['q'])->orLike('manufacturer_code', $filters['q'])->groupEnd();
        }
        if ($filters['brand'] !== '') {
            $products->where('brand', $filters['brand']);
        }
        if ($filters['scale'] !== '') {
            $products->where('scale', $filters['scale']);
        }

        $rows = $products->orderBy('brand')->orderBy('name')->paginate(12);
        $products->pager->only(['q', 'brand', 'scale']);
        $images = new ProductImageModel();
        foreach ($rows as &$row) {
            $image = $images->where(['tenant_id' => $tenantId, 'product_id' => $row['id'], 'is_primary' => 1])->first();
            $row['image_id'] = $image['id'] ?? null;
        }
        unset($row);

        $db = db_connect();
        return view('products/index', [
            'products' => $rows,
            'pager' => $products->pager,
            'filters' => $filters,
            'brands' => $db->table('products')->select('brand')->where('tenant_id', $tenantId)->where('brand !=', '')->groupBy('brand')->orderBy('brand')->get()->getResultArray(),
            'scales' => $db->table('products')->select('scale')->where('tenant_id', $tenantId)->where('scale IS NOT NULL', null, false)->where('scale !=', '')->groupBy('scale')->orderBy('scale')->get()->getResultArray(),
        ]);
    }

    public function create(): string
    {
        return view('products/form', ['product' => null, 'images' => []]);
    }

    public function edit(int $id): string
    {
        $product = $this->owned($id);
        $images = (new ProductImageModel())->where(['tenant_id' => $this->tenant(), 'product_id' => $product['id']])->orderBy('is_primary', 'DESC')->orderBy('sort_order')->get()->getResultArray();

        return view('products/form', ['product' => $product, 'images' => $images]);
    }

    public function store()
    {
        return $this->save();
    }

    public function update(int $id)
    {
        return $this->save($this->owned($id));
    }

    public function delete(int $id)
    {
        $product = $this->owned($id);
        foreach ((new ProductImageModel())->where(['tenant_id' => $this->tenant(), 'product_id' => $product['id']])->findAll() as $image) {
            $this->deleteImageFile($image);
        }
        db_connect()->table('product_images')->where(['tenant_id' => $this->tenant(), 'product_id' => $product['id']])->delete();
        (new ProductModel())->delete($product['id']);

        return redirect()->to('/carrinhos')->with('message', 'Carrinho excluído.');
    }

    public function deleteImage(int $productId, int $imageId)
    {
        $product = $this->owned($productId);
        $images = new ProductImageModel();
        $image = $images->where(['id' => $imageId, 'tenant_id' => $this->tenant(), 'product_id' => $product['id']])->first();
        if (! $image) {
            throw PageNotFoundException::forPageNotFound();
        }

        $wasPrimary = (int) $image['is_primary'] === 1;
        $this->deleteImageFile($image);
        $images->delete($image['id']);
        if ($wasPrimary) {
            $replacement = $images->where(['tenant_id' => $this->tenant(), 'product_id' => $product['id']])->orderBy('sort_order')->first();
            if ($replacement) {
                $images->update($replacement['id'], ['is_primary' => 1]);
            }
        }

        return redirect()->to('/carrinhos/' . $product['id'] . '/editar')->with('message', 'Foto removida da galeria.');
    }

    public function image(int $id)
    {
        $row = (new ProductImageModel())->where(['id' => $id, 'tenant_id' => $this->tenant()])->first();
        if (! $row) {
            throw PageNotFoundException::forPageNotFound();
        }
        $path = WRITEPATH . 'uploads/' . $row['path'];
        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->setHeader('Content-Type', $row['mime_type'])->setHeader('X-Content-Type-Options', 'nosniff')->setBody((string) file_get_contents($path));
    }

    private function save(?array $product = null)
    {
        $rules = [
            'brand' => 'required|max_length[100]',
            'name' => 'required|max_length[160]',
            'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $gallery = $this->request->getFileMultiple('gallery');
        foreach ($gallery as $file) {
            $error = $this->validImage($file);
            if ($error !== null) {
                return redirect()->back()->withInput()->with('errors', ['gallery' => $error]);
            }
        }

        $model = new ProductModel();
        $data = $this->request->getPost(['brand', 'name', 'manufacturer_code', 'scale', 'series', 'notes']);
        if ($product) {
            $model->update($product['id'], $data);
            $id = (int) $product['id'];
        } else {
            $model->insert($data + ['tenant_id' => $this->tenant()]);
            $id = (int) $model->getInsertID();
        }

        $primary = $this->request->getFile('image');
        if ($primary && $primary->isValid() && ! $primary->hasMoved()) {
            $images = new ProductImageModel();
            $images->where(['tenant_id' => $this->tenant(), 'product_id' => $id])->set('is_primary', 0)->update();
            $this->storeImage($primary, $id, true);
        }
        foreach ($gallery as $file) {
            if ($file->getError() !== UPLOAD_ERR_NO_FILE) {
                $this->storeImage($file, $id, false);
            }
        }

        return redirect()->to('/carrinhos')->with('message', $product ? 'Carrinho atualizado.' : 'Carrinho cadastrado.');
    }

    private function storeImage(UploadedFile $file, int $productId, bool $primary): void
    {
        $tenantId = $this->tenant();
        $directory = WRITEPATH . 'uploads/products/' . $tenantId;
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $filename = $file->getRandomName();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $file->move($directory, $filename);
        $maxSort = (new ProductImageModel())->selectMax('sort_order')->where(['tenant_id' => $tenantId, 'product_id' => $productId])->first();
        (new ProductImageModel())->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'path' => 'products/' . $tenantId . '/' . $filename,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'sort_order' => ((int) ($maxSort['sort_order'] ?? -1)) + 1,
            'is_primary' => $primary ? 1 : 0,
        ]);
    }

    private function validImage(UploadedFile $file): ?string
    {
        if ($file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024) {
            return 'Cada foto da galeria deve ser válida e ter no máximo 5 MB.';
        }
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'A galeria aceita apenas imagens JPEG, PNG ou WebP.';
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function owned(int $id): array
    {
        $product = (new ProductModel())->where(['id' => $id, 'tenant_id' => $this->tenant()])->first();
        if (! $product) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $product;
    }

    /** @param array<string, mixed> $image */
    private function deleteImageFile(array $image): void
    {
        $path = WRITEPATH . 'uploads/' . $image['path'];
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }
}
