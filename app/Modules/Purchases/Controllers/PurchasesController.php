<?php

declare(strict_types=1);

namespace App\Modules\Purchases\Controllers;

use App\Controllers\BaseController;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Purchases\Services\PurchaseService;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class PurchasesController extends BaseController
{
    public function index(): string
    {
        return view('purchases/index', ['purchases' => $this->service()->all($this->tenant())]);
    }

    public function create(): string
    {
        return view('purchases/form', $this->service()->formData($this->tenant()));
    }

    public function store()
    {
        $rules = ['store_name' => 'required|max_length[160]', 'purchased_at' => 'required|valid_date[Y-m-d]', 'quantity' => 'required|is_natural_no_zero', 'unit_price' => 'required|decimal|greater_than[0]', 'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $this->service()->register($this->tenant(), $this->request->getPost(), $this->request->getFile('image'));
        } catch (\InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('errors', ['new_product_name' => $exception->getMessage()]);
        }
        return redirect()->to('/compras')->with('message', 'Compra registrada e adicionada à coleção.');
    }

    private function service(): PurchaseService
    {
        $db = db_connect();
        return new PurchaseService($db, new CatalogService($db));
    }
    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }
}
