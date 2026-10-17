<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class ProductsController extends BaseController
{
    public function index(): string
    {
        $filters = ['q' => trim((string) $this->request->getGet('q')), 'brand' => trim((string) $this->request->getGet('brand')), 'scale' => trim((string) $this->request->getGet('scale'))];

        return view('products/index', $this->service()->paginated($this->tenant(), $filters));
    }

    public function create(): string
    {
        return view('products/form', ['product' => null, 'images' => []]);
    }

    public function edit(int $id): string
    {
        $product = $this->service()->find($this->tenant(), $id);

        return view('products/form', ['product' => $product, 'images' => $this->service()->images($this->tenant(), $id)]);
    }

    public function store()
    {
        return $this->save();
    }

    public function update(int $id)
    {
        return $this->save($id);
    }

    public function delete(int $id)
    {
        $this->service()->delete($this->tenant(), $id);

        return redirect()->to('/carrinhos')->with('message', 'Carrinho excluído.');
    }

    public function deleteImage(int $productId, int $imageId)
    {
        $this->service()->deleteImage($this->tenant(), $productId, $imageId);

        return redirect()->to('/carrinhos/' . $productId . '/editar')->with('message', 'Foto removida da galeria.');
    }

    public function image(int $id)
    {
        $image = $this->service()->image($this->tenant(), $id);

        return $this->response
            ->setHeader('Content-Type', $image['mime_type'])
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) file_get_contents(WRITEPATH . 'uploads/' . $image['path']));
    }

    private function save(?int $id = null)
    {
        $rules = ['brand' => 'required|max_length[100]', 'name' => 'required|max_length[160]', 'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $gallery = $this->request->getFileMultiple('gallery');
        if (($error = $this->service()->validateGallery($gallery)) !== null) {
            return redirect()->back()->withInput()->with('errors', ['gallery' => $error]);
        }

        $this->service()->save($this->tenant(), $this->request->getPost(['brand', 'name', 'manufacturer_code', 'scale', 'series', 'notes']), $id, $this->request->getFile('image'), $gallery);

        return redirect()->to('/carrinhos')->with('message', $id === null ? 'Carrinho cadastrado.' : 'Carrinho atualizado.');
    }

    private function service(): CatalogService
    {
        return new CatalogService(db_connect());
    }

    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }
}
