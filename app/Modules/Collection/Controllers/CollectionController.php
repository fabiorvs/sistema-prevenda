<?php

declare(strict_types=1);

namespace App\Modules\Collection\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class CollectionController extends BaseController
{
    public function index(): string
    {
        $tenantId = (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
        $items = db_connect()->table('collection_items')
            ->select('collection_items.*, products.name AS product_name, products.brand, products.scale, product_images.id AS image_id', false)
            ->join('products', 'products.id = collection_items.product_id')
            ->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')
            ->where('collection_items.tenant_id', $tenantId)
            ->orderBy('collection_items.received_at', 'DESC')
            ->get()->getResultArray();
        return view('collection/index', ['items' => $items]);
    }

    public function updateLocation(int $id)
    {
        if (! $this->validate(['location' => 'permit_empty|max_length[120]', 'notes' => 'permit_empty|max_length[2000]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $tenantId = (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
        $updated = db_connect()->table('collection_items')->where(['id' => $id, 'tenant_id' => $tenantId])->update([
            'location' => trim((string) $this->request->getPost('location')) ?: null,
            'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        if (! $updated) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return redirect()->to('/colecao')->with('message', 'Localização da miniatura atualizada.');
    }
}
