<?php

declare(strict_types=1);

namespace App\Modules\Purchases\Controllers;

use App\Controllers\BaseController;
use App\Modules\Purchases\Services\PurchaseService;
use CodeIgniter\Exceptions\PageNotFoundException;

final class PurchasesController extends BaseController
{
    public function index(): string
    {
        return view('purchases/index', ['purchases' => $this->service()->all($this->tenant())]);
    }

    public function create(): string
    {
        return view('purchases/form', $this->service()->formData($this->tenant()) + ['purchase' => null]);
    }

    public function edit(int $id): string
    {
        return view('purchases/form', $this->service()->formData($this->tenant()) + [
            'purchase' => $this->service()->find($this->tenant(), $id),
        ]);
    }

    public function store()
    {
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->service()->register($this->tenant(), $this->request->getPost(), $this->request->getFile('image'));
        } catch (\InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('errors', ['purchase' => $exception->getMessage()]);
        }

        return redirect()->to('/compras')->with('message', 'Compra registrada e adicionada à coleção.');
    }

    public function update(int $id)
    {
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->service()->update($this->tenant(), $id, $this->request->getPost(), $this->request->getFile('image'));
        } catch (\InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('errors', ['purchase' => $exception->getMessage()]);
        }

        return redirect()->to('/compras')->with('message', 'Compra atualizada.');
    }

    private function service(): PurchaseService
    {
        return service('purchaseService');
    }

    private function tenant(): int
    {
        $tenantId = service('activeTenantResolver')->forUser((int) auth()->user()->id);
        if ($tenantId === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $tenantId;
    }

    /** @return array<string, string> */
    private function rules(): array
    {
        return [
            'store_name' => 'required|max_length[160]',
            'purchased_at' => 'required|valid_date[Y-m-d]',
            'quantity' => 'required|is_natural_no_zero',
            'unit_price' => 'required|decimal|greater_than[0]',
            'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]',
        ];
    }
}
