<?php

declare(strict_types=1);

namespace App\Modules\Garage\Controllers;

use App\Controllers\BaseController;
use App\Modules\Catalog\Models\ProductImageModel;
use App\Modules\Garage\Models\GarageItemModel;
use App\Modules\Garage\Models\GaragePaymentModel;
use App\Modules\Garage\Services\GarageStatus;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;

final class GarageController extends BaseController
{
    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }

    public function index(): string
    {
        $tenantId = $this->tenant();
        $sellerId = (int) $this->request->getGet('seller_id');
        $status = (string) $this->request->getGet('status');
        $items = $this->itemsQuery($tenantId);
        $items->where('garage_items.status', 'stored');

        if ($sellerId > 0) {
            $items->where('garage_items.seller_id', $sellerId);
        }
        if ($status === 'stored') {
            $items->where('garage_items.status', $status);
        }

        $rows = $items
            ->orderBy('garage_items.status', 'ASC')
            ->orderBy('garage_items.acquired_at', 'DESC')
            ->paginate(12);

        return view('garage/index', [
            'items' => $rows,
            'pager' => $items->pager,
            'sellerSummary' => $this->sellerSummary($tenantId),
            'sellers' => db_connect()->table('sellers')->where('tenant_id', $tenantId)->orderBy('name')->get()->getResultArray(),
            'labels' => GarageStatus::labels(),
            'filters' => ['seller_id' => $sellerId, 'status' => $status],
        ]);
    }

    public function create(): string
    {
        $tenantId = $this->tenant();
        $db = db_connect();

        return view('garage/form', [
            'sellers' => $db->table('sellers')->where('tenant_id', $tenantId)->orderBy('name')->get()->getResultArray(),
            'products' => $db->table('products')->where('tenant_id', $tenantId)->orderBy('brand')->orderBy('name')->get()->getResultArray(),
            'brands' => $db->table('products')
                ->select('brand')
                ->where('tenant_id', $tenantId)
                ->where('brand !=', '')
                ->groupBy('brand')
                ->orderBy('brand')
                ->get()
                ->getResultArray(),
        ]);
    }

    public function store()
    {
        $rules = [
            'seller_id' => 'required|is_natural_no_zero',
            'quantity' => 'required|is_natural_no_zero',
            'unit_price' => 'required|decimal|greater_than[0]',
            'acquired_at' => 'required|valid_date[Y-m-d]',
            'next_payment_date' => 'permit_empty|valid_date[Y-m-d]',
            'initial_payment' => 'permit_empty|decimal|greater_than_equal_to[0]',
            'payment_method' => 'permit_empty|in_list[pix,transferencia,cartao,dinheiro,outro]',
            'image' => 'if_exist|max_size[image,5120]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $tenantId = $this->tenant();
        $db = db_connect();
        $sellerId = (int) $this->request->getPost('seller_id');
        if ($db->table('sellers')->where(['id' => $sellerId, 'tenant_id' => $tenantId])->countAllResults() === 0) {
            return redirect()->back()->withInput()->with('errors', ['seller_id' => 'Vendedor inválido.']);
        }

        $productId = (int) $this->request->getPost('product_id');
        if ($productId > 0 && $db->table('products')->where(['id' => $productId, 'tenant_id' => $tenantId])->countAllResults() === 0) {
            return redirect()->back()->withInput()->with('errors', ['product_id' => 'Carrinho inválido.']);
        }

        $newName = trim((string) $this->request->getPost('new_product_name'));
        if ($productId === 0 && $newName === '') {
            return redirect()->back()->withInput()->with('errors', ['product_id' => 'Selecione um carrinho ou cadastre um novo.']);
        }

        $brand = trim((string) $this->request->getPost('new_product_brand'));
        if ($brand === '__new__') {
            $brand = trim((string) $this->request->getPost('custom_product_brand'));
            if ($productId === 0 && $brand === '') {
                return redirect()->back()->withInput()->with('errors', ['new_product_brand' => 'Informe a nova marca.']);
            }
        }

        $quantity = (int) $this->request->getPost('quantity');
        $unitPrice = $this->money($this->request->getPost('unit_price'));
        $initialPayment = $this->money($this->request->getPost('initial_payment'));
        $acquiredAt = (string) $this->request->getPost('acquired_at');
        $total = $quantity * $unitPrice;
        if ($initialPayment > $total) {
            return redirect()->back()->withInput()->with('errors', ['initial_payment' => 'O pagamento inicial não pode exceder o valor total.']);
        }

        $db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        if ($productId === 0) {
            $db->table('products')->insert([
                'tenant_id' => $tenantId,
                'brand' => $brand ?: 'Sem marca',
                'name' => $newName,
                'scale' => trim((string) $this->request->getPost('new_product_scale')) ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $productId = (int) $db->insertID();
            $this->saveProductImage($tenantId, $productId);
        }

        $model = new GarageItemModel();
        $model->insert([
            'tenant_id' => $tenantId,
            'seller_id' => $sellerId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => number_format($unitPrice, 2, '.', ''),
            'acquired_at' => $acquiredAt,
            'next_payment_date' => $this->request->getPost('next_payment_date') ?: null,
            'status' => 'stored',
            'notes' => $this->request->getPost('notes'),
        ]);
        $itemId = (int) $model->getInsertID();

        if ($initialPayment > 0) {
            (new GaragePaymentModel())->insert([
                'tenant_id' => $tenantId,
                'garage_item_id' => $itemId,
                'amount' => number_format($initialPayment, 2, '.', ''),
                'method' => $this->request->getPost('payment_method') ?: 'pix',
                // Para lançamentos retroativos, o pagamento inicial pertence à data
                // da compra informada, não ao momento em que foi cadastrado no sistema.
                'paid_at' => $acquiredAt . ' ' . date('H:i:s'),
                'notes' => 'Pagamento inicial',
            ]);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new \RuntimeException('Não foi possível cadastrar o item na garagem.');
        }

        return redirect()->to('/garagem/' . $itemId)->with('message', 'Item adicionado à garagem.');
    }

    public function show(int $id): string
    {
        $item = $this->owned($id);
        $payments = db_connect()->table('garage_payments')
            ->where(['garage_item_id' => $id, 'tenant_id' => $this->tenant()])
            ->orderBy('paid_at', 'DESC')
            ->get()
            ->getResultArray();
        $attachments = db_connect()->table('attachments')
            ->where(['tenant_id' => $this->tenant(), 'owner_type' => 'garage', 'owner_id' => $id])
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return view('garage/show', [
            'item' => $item,
            'payments' => $payments,
            'attachments' => $attachments,
            'labels' => GarageStatus::labels(),
        ]);
    }

    public function edit(int $id): string
    {
        return view('garage/edit', [
            'item' => $this->owned($id),
            'sellers' => db_connect()->table('sellers')->where('tenant_id', $this->tenant())->orderBy('name')->get()->getResultArray(),
        ]);
    }

    public function update(int $id)
    {
        $rules = ['seller_id' => 'required|is_natural_no_zero', 'unit_price' => 'required|decimal|greater_than[0]', 'acquired_at' => 'required|valid_date[Y-m-d]', 'next_payment_date' => 'permit_empty|valid_date[Y-m-d]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $item = $this->owned($id);
        $total = (int) $item['quantity'] * $this->money($this->request->getPost('unit_price'));
        if ($total + 0.0001 < (float) $item['paid']) return redirect()->back()->withInput()->with('errors', ['unit_price' => 'O valor não pode ser menor que o já pago.']);
        (new GarageItemModel())->update($id, ['seller_id' => (int) $this->request->getPost('seller_id'), 'unit_price' => number_format($this->money($this->request->getPost('unit_price')), 2, '.', ''), 'acquired_at' => $this->request->getPost('acquired_at'), 'next_payment_date' => $this->request->getPost('next_payment_date') ?: null, 'notes' => $this->request->getPost('notes')]);
        return redirect()->to('/garagem/' . $id)->with('message', 'Item da garagem atualizado.');
    }

    public function addPayment(int $id)
    {
        $rules = [
            'amount' => 'required|decimal|greater_than[0]',
            'method' => 'required|in_list[pix,transferencia,cartao,dinheiro,outro]',
            'paid_at' => 'required|valid_date[Y-m-d]',
            'next_payment_date' => 'permit_empty|valid_date[Y-m-d]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $item = $this->owned($id);
        if ($item['status'] === 'canceled') {
            return redirect()->to('/garagem/' . $id)->with('errors', [
                'payment' => 'Não é possível registrar pagamento em um item cancelado.',
            ]);
        }
        $amount = $this->money($this->request->getPost('amount'));
        $remaining = max(0, (float) $item['total'] - (float) $item['paid']);
        if ($amount > $remaining + 0.0001) {
            return redirect()->back()->withInput()->with('errors', [
                'amount' => 'O pagamento excede o saldo de R$ ' . number_format($remaining, 2, ',', '.') . '.',
            ]);
        }

        (new GaragePaymentModel())->insert([
            'tenant_id' => $this->tenant(),
            'garage_item_id' => $id,
            'amount' => number_format($amount, 2, '.', ''),
            'method' => $this->request->getPost('method'),
            'paid_at' => $this->request->getPost('paid_at') . ' ' . date('H:i:s'),
            'notes' => $this->request->getPost('notes'),
        ]);

        (new GarageItemModel())->update($id, [
            'next_payment_date' => $amount >= $remaining - 0.0001
                ? null
                : ($this->request->getPost('next_payment_date') ?: null),
        ]);

        return redirect()->to('/garagem/' . $id)->with('message', 'Pagamento registrado.');
    }

    public function updateStatus(int $id)
    {
        $status = (string) $this->request->getPost('status');
        if ($status !== 'canceled') {
            return redirect()->back()->with('errors', [
                'status' => 'A logística é atualizada exclusivamente em Entregas.',
            ]);
        }

        $item = $this->owned($id);
        if ($item['status'] === 'canceled') {
            return redirect()->to('/garagem/' . $id)->with('message', 'Este item já está cancelado.');
        }

        (new GarageItemModel())->update($item['id'], ['status' => 'canceled']);

        return redirect()->to('/garagem/' . $id)->with('message', 'Item da garagem cancelado.');
    }

    private function itemsQuery(int $tenantId): GarageItemModel
    {
        return (new GarageItemModel())
            ->select('garage_items.*, sellers.name AS seller_name, products.brand, products.name AS product_name, products.scale, MAX(product_images.id) AS image_id, COALESCE(SUM(garage_payments.amount), 0) AS paid, garage_items.quantity * garage_items.unit_price AS total', false)
            ->join('sellers', 'sellers.id = garage_items.seller_id')
            ->join('products', 'products.id = garage_items.product_id')
            ->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')
            ->join('garage_payments', 'garage_payments.garage_item_id = garage_items.id', 'left')
            ->where('garage_items.tenant_id', $tenantId)
            ->groupBy('garage_items.id');
    }

    /**
     * @return array<string, mixed>
     */
    private function owned(int $id): array
    {
        $item = $this->itemsQuery($this->tenant())
            ->where('garage_items.id', $id)
            ->first();
        if ($item === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        $item['balance'] = max(0, (float) $item['total'] - (float) $item['paid']);

        return $item;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sellerSummary(int $tenantId): array
    {
        $items = $this->itemsQuery($tenantId)
            ->where('garage_items.status', 'stored')
            ->findAll();
        $summary = [];
        foreach ($items as $item) {
            $key = (int) $item['seller_id'];
            $summary[$key] ??= [
                'seller_id' => $key,
                'seller_name' => $item['seller_name'],
                'items' => 0,
                'total' => 0.0,
                'paid' => 0.0,
            ];
            $summary[$key]['items'] += (int) $item['quantity'];
            $summary[$key]['total'] += (float) $item['total'];
            $summary[$key]['paid'] += (float) $item['paid'];
        }

        return array_values($summary);
    }

    private function saveProductImage(int $tenantId, int $productId): void
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
        (new ProductImageModel())->insert([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'path' => 'products/' . $tenantId . '/' . $filename,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'sort_order' => 0,
            'is_primary' => 1,
        ]);
    }

    private function money(mixed $value): float
    {
        return round(max(0, (float) str_replace(',', '.', (string) $value)), 2);
    }
}
