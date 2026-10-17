<?php

declare(strict_types=1);

namespace App\Modules\Imports\Controllers;

use App\Controllers\BaseController;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Imports\Services\ImportService;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class ImportsController extends BaseController
{
    public function index(): string
    {
        return view('imports/index', ['imports' => $this->service()->all($this->tenant())]);
    }

    public function create(): string
    {
        return view('imports/form', $this->service()->formData($this->tenant()));
    }

    public function store()
    {
        $rules = ['supplier_name' => 'required|max_length[160]', 'purchased_at' => 'required|valid_date[Y-m-d]', 'item_amount' => 'required|decimal|greater_than[0]', 'exchange_rate' => 'required|decimal|greater_than[0]', 'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        try { $this->service()->register($this->tenant(), $this->request->getPost(), $this->request->getFile('image')); }
        catch (\InvalidArgumentException $exception) { return redirect()->back()->withInput()->with('errors', ['new_product_name' => $exception->getMessage()]); }
        return redirect()->to('/importacoes')->with('message', 'Importação cadastrada.');
    }

    public function receive(int $id)
    {
        $received = $this->service()->receive($this->tenant(), $id);
        return redirect()->to('/importacoes')->with('message', $received ? 'Importação recebida e adicionada à coleção.' : 'Esta importação já foi adicionada à coleção.');
    }

    private function service(): ImportService { $db = db_connect(); return new ImportService($db, new CatalogService($db)); }
    private function tenant(): int { return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id); }
}
