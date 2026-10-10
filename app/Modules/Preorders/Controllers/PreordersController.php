<?php

declare(strict_types=1);

namespace App\Modules\Preorders\Controllers;

use App\Controllers\BaseController;
use App\Modules\Catalog\Models\ProductImageModel;
use App\Modules\Deliveries\Services\DeliveryService;
use App\Modules\Deliveries\Services\DeliveryStatus;
use App\Modules\Finance\Services\FinancialEntryService;
use App\Modules\Preorders\Models\PreorderItemModel;
use App\Modules\Preorders\Models\PreorderModel;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;

final class PreordersController extends BaseController
{
    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))
            ->forUser((int) auth()->user()->id);
    }

    public function index(): string
    {
        $tenantId = $this->tenant();
        $sellerId = (int) $this->request->getGet('seller_id');
        $model = new PreorderModel();
        $query = $model
            ->select('preorders.*, sellers.name AS seller_name, COALESCE(SUM(preorder_items.quantity * preorder_items.unit_price), 0) AS items_total', false)
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->join('preorder_items', 'preorder_items.preorder_id = preorders.id', 'left')
            ->where('preorders.tenant_id', $tenantId)
            ->whereNotIn('preorders.status', [
                DeliveryStatus::AWAITING_SHIPMENT,
                DeliveryStatus::IN_TRANSIT,
                DeliveryStatus::DELIVERED,
                DeliveryStatus::CANCELED,
            ])
            ->groupBy('preorders.id');

        if ($sellerId > 0) {
            $query->where('preorders.seller_id', $sellerId);
        }

        $preorders = $query
            ->orderBy('preorders.expected_delivery_date IS NULL', 'ASC', false)
            ->orderBy('preorders.expected_delivery_date', 'ASC')
            ->paginate(15);

        $db = db_connect();
        foreach ($preorders as &$preorder) {
            $item = $db->table('preorder_items')
                ->select('products.name AS product_name, products.brand, product_images.id AS image_id')
                ->join('products', 'products.id = preorder_items.product_id')
                ->join('product_images', 'product_images.product_id = products.id AND product_images.is_primary = 1', 'left')
                ->where([
                    'preorder_items.preorder_id' => $preorder['id'],
                    'preorder_items.tenant_id' => $tenantId,
                ])
                ->orderBy('preorder_items.id')
                ->get(1)
                ->getRowArray();

            $allocated = (float) ($db->table('financial_entries')
                ->select('COALESCE(SUM(payment_allocations.amount), 0) AS paid_amount', false)
                ->join('payment_allocations', 'payment_allocations.financial_entry_id = financial_entries.id', 'left')
                ->where([
                    'financial_entries.preorder_id' => $preorder['id'],
                    'financial_entries.tenant_id' => $tenantId,
                ])
                ->get()
                ->getRowArray()['paid_amount'] ?? 0);

            $preorder['product_name'] = $item['product_name'] ?? 'Sem item';
            $preorder['brand'] = $item['brand'] ?? '';
            $preorder['image_id'] = $item['image_id'] ?? null;
            $preorder['total'] = (float) $preorder['items_total']
                + (float) $preorder['shipping_amount']
                - (float) $preorder['discount_amount'];
            $preorder['paid'] = (float) $preorder['down_payment_amount'] + $allocated;
            $preorder['balance'] = max(0, $preorder['total'] - $preorder['paid']);
        }
        unset($preorder);

        return view('preorders/index', [
            'preorders' => $preorders,
            'pager' => $model->pager,
            'sellers' => $db->table('sellers')
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get()
                ->getResultArray(),
            'filters' => ['seller_id' => $sellerId],
        ]);
    }

    public function show(int $id): string
    {
        $tenantId = $this->tenant();
        $db = db_connect();
        $preorder = (new DeliveryService($db))->findOrder($tenantId, $id);

        if ($preorder === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($preorder['status'] !== DeliveryStatus::CANCELED) {
            (new FinancialEntryService($db))->syncForPreorder($tenantId, $id);
        }

        $financialEntry = $db->table('financial_entries')
            ->where(['tenant_id' => $tenantId, 'preorder_id' => $id])
            ->orderBy('id')
            ->get()
            ->getRowArray();
        $payments = $db->table('payments')
            ->where(['tenant_id' => $tenantId, 'preorder_id' => $id, 'status' => 'confirmed'])
            ->orderBy('paid_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
        $statusHistory = $db->table('preorder_status_history')
            ->where(['tenant_id' => $tenantId, 'preorder_id' => $id])
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $itemsTotal = 0.0;
        foreach ($preorder['items'] as $item) {
            $itemsTotal += (int) $item['quantity'] * (float) $item['unit_price'];
        }

        $allocated = 0.0;
        if ($financialEntry !== null) {
            $allocated = (float) ($db->table('payment_allocations')
                ->select('COALESCE(SUM(amount), 0) AS paid_amount', false)
                ->where('financial_entry_id', $financialEntry['id'])
                ->get()
                ->getRowArray()['paid_amount'] ?? 0);
        }

        $total = $itemsTotal
            + (float) $preorder['shipping_amount']
            - (float) $preorder['discount_amount'];
        $paid = max(0, (float) $preorder['down_payment_amount'] + $allocated);
        $preorder['items_total'] = $itemsTotal;
        $preorder['total'] = $total;
        $preorder['paid'] = min($total, $paid);
        $preorder['balance'] = max(0, $total - $paid);

        return view('preorders/show', [
            'preorder' => $preorder,
            'financialEntry' => $financialEntry,
            'payments' => $payments,
            'statusHistory' => $statusHistory,
            'statusLabels' => DeliveryStatus::labels(),
        ]);
    }

    public function create(): string
    {
        $tenantId = $this->tenant();
        $db = db_connect();

        return view('preorders/form', [
            'sellers' => $db->table('sellers')->where('tenant_id', $tenantId)->orderBy('name')->get()->getResultArray(),
            'products' => $db->table('products')->where('tenant_id', $tenantId)->orderBy('name')->get()->getResultArray(),
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

    public function edit(int $id): string
    {
        $preorder = (new DeliveryService(db_connect()))->findOrder($this->tenant(), $id);
        if ($preorder === null) throw PageNotFoundException::forPageNotFound();
        return view('preorders/edit', ['preorder' => $preorder, 'item' => $preorder['items'][0] ?? null, 'sellers' => db_connect()->table('sellers')->where('tenant_id', $this->tenant())->orderBy('name')->get()->getResultArray()]);
    }

    public function update(int $id)
    {
        $rules = ['seller_id' => 'required|is_natural_no_zero', 'order_date' => 'required|valid_date[Y-m-d]', 'expected_delivery_date' => 'permit_empty|valid_date[Y-m-d]', 'quantity' => 'required|is_natural_no_zero', 'unit_price' => 'required|decimal|greater_than[0]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $tenantId = $this->tenant(); $db = db_connect(); $preorder = (new DeliveryService($db))->findOrder($tenantId, $id);
        if ($preorder === null || $preorder['items'] === []) throw PageNotFoundException::forPageNotFound();
        if ((int) $this->request->getPost('quantity') < (int) $preorder['items'][0]['received_quantity']) return redirect()->back()->withInput()->with('errors', ['quantity' => 'A quantidade não pode ser menor que a já recebida.']);
        $db->transStart();
        (new PreorderModel())->update($id, ['seller_id' => (int) $this->request->getPost('seller_id'), 'order_date' => $this->request->getPost('order_date'), 'expected_delivery_date' => $this->request->getPost('expected_delivery_date') ?: null, 'notes' => $this->request->getPost('notes')]);
        (new PreorderItemModel())->update($preorder['items'][0]['id'], ['quantity' => (int) $this->request->getPost('quantity'), 'unit_price' => $this->money($this->request->getPost('unit_price'))]);
        (new FinancialEntryService($db))->syncForPreorder($tenantId, $id); $db->transComplete();
        return redirect()->to('/pre-vendas/' . $id)->with('message', 'Pré-venda atualizada.');
    }

    public function markCompleteArrival(int $id)
    {
        $tenantId = $this->tenant();
        $delivery = new DeliveryService(db_connect());
        $preorder = $delivery->findOrder($tenantId, $id);

        if ($preorder === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $quantities = [];
        foreach ($preorder['items'] as $item) {
            $pending = (int) $item['quantity'] - (int) $item['received_quantity'];
            if ($pending > 0) {
                $quantities[(int) $item['id']] = $pending;
            }
        }

        if ($quantities === []) {
            return redirect()->to('/pre-vendas/' . $id)->with('message', 'Esta pré-venda já está disponível no vendedor.');
        }

        try {
            $delivery->receiveItems(
                $tenantId,
                $id,
                (int) auth()->user()->id,
                $quantities,
                [
                    'actual_delivery_date' => date('Y-m-d'),
                    'notes' => 'Chegada completa marcada na pré-venda.',
                ],
            );
        } catch (\InvalidArgumentException $exception) {
            return redirect()->to('/pre-vendas/' . $id)->with('errors', ['arrival' => $exception->getMessage()]);
        }

        return redirect()->to('/pre-vendas/' . $id)->with('message', 'Chegada completa confirmada. Os itens já podem entrar em um envio agrupado.');
    }

    public function cancel(int $id)
    {
        $tenantId = $this->tenant();
        $preorder = (new DeliveryService(db_connect()))->findOrder($tenantId, $id);

        if ($preorder === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        if ($preorder['status'] === DeliveryStatus::CANCELED) {
            return redirect()->to('/pre-vendas/' . $id)->with('message', 'Esta pré-venda já está cancelada.');
        }

        $db = db_connect();
        $now = gmdate('Y-m-d H:i:s');
        $db->transStart();
        (new PreorderModel())->update($id, ['status' => DeliveryStatus::CANCELED]);
        $db->table('preorder_status_history')->insert([
            'tenant_id' => $tenantId,
            'preorder_id' => $id,
            'old_status' => $preorder['status'],
            'new_status' => DeliveryStatus::CANCELED,
            'actor_user_id' => (int) auth()->user()->id,
            'created_at' => $now,
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new \RuntimeException('Não foi possível cancelar a pré-venda.');
        }

        return redirect()->to('/pre-vendas/' . $id)->with('message', 'Pré-venda cancelada.');
    }

    public function store()
    {
        $rules = [
            'seller_id' => 'required|is_natural_no_zero',
            'order_date' => 'required|valid_date[Y-m-d]',
            'quantity' => 'required',
            'unit_price' => 'required',
            'down_payment_method' => 'permit_empty|in_list[pix,transferencia,cartao,dinheiro,outro]',
            'new_product_image' => 'if_exist|max_size[new_product_image,5120]|is_image[new_product_image]|mime_in[new_product_image,image/jpeg,image/png,image/webp]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $tenantId = $this->tenant();
        $db = db_connect();
        $sellerExists = $db->table('sellers')
            ->where(['id' => (int) $this->request->getPost('seller_id'), 'tenant_id' => $tenantId])
            ->countAllResults();

        if ($sellerExists === 0) {
            return redirect()->back()->withInput()->with('errors', ['seller_id' => 'Vendedor inválido.']);
        }

        $products = (array) $this->request->getPost('product_id');
        $names = (array) $this->request->getPost('new_product_name');
        $brands = (array) $this->request->getPost('new_product_brand');
        $customBrands = (array) $this->request->getPost('custom_product_brand');
        $scales = (array) $this->request->getPost('new_product_scale');
        $quantities = (array) $this->request->getPost('quantity');
        $prices = (array) $this->request->getPost('unit_price');

        if (count($quantities) !== count($prices) || $quantities === []) {
            return redirect()->back()->withInput()->with('errors', [
                'items' => 'Inclua ao menos um item válido.',
            ]);
        }

        $downPayment = (float) $this->money($this->request->getPost('down_payment_amount'));
        $downPaymentMethod = trim((string) $this->request->getPost('down_payment_method'));
        if ($downPayment > 0 && $downPaymentMethod === '') {
            return redirect()->back()->withInput()->with('errors', [
                'down_payment_method' => 'Informe a forma de pagamento do sinal.',
            ]);
        }

        $itemsTotal = 0.0;
        foreach ($quantities as $index => $quantity) {
            $itemsTotal += max(1, (int) $quantity) * (float) $this->money($prices[$index] ?? 0);
        }
        if ($downPayment > $itemsTotal) {
            return redirect()->back()->withInput()->with('errors', [
                'down_payment_amount' => 'O sinal não pode exceder o valor total da pré-venda.',
            ]);
        }

        $db->transStart();
        $reference = 'PV-' . str_pad((string) (int) (microtime(true) % 100000), 5, '0', STR_PAD_LEFT);
        $model = new PreorderModel();
        $model->insert([
            'tenant_id' => $tenantId,
            'seller_id' => (int) $this->request->getPost('seller_id'),
            'reference' => $reference,
            'order_date' => $this->request->getPost('order_date'),
            'expected_delivery_date' => $this->request->getPost('expected_delivery_date') ?: null,
            'status' => DeliveryStatus::AWAITING_RELEASE,
            'shipping_amount' => '0.00',
            'discount_amount' => '0.00',
            'down_payment_amount' => number_format($downPayment, 2, '.', ''),
            'down_payment_method' => $downPayment > 0 ? $downPaymentMethod : null,
            'notes' => $this->request->getPost('notes'),
        ]);
        $preorderId = (int) $model->getInsertID();
        $items = new PreorderItemModel();

        foreach ($quantities as $index => $quantity) {
            $productId = (int) ($products[$index] ?? 0);
            $isNew = false;
            $brand = trim((string) ($brands[$index] ?? ''));

            if ($brand === '__new__') {
                $brand = trim((string) ($customBrands[$index] ?? ''));
            }

            if ($productId === 0 && trim((string) ($names[$index] ?? '')) !== '') {
                $db->table('products')->insert([
                    'tenant_id' => $tenantId,
                    'brand' => $brand ?: 'Sem marca',
                    'name' => trim((string) $names[$index]),
                    'scale' => trim((string) ($scales[$index] ?? '')) ?: null,
                    'created_at' => gmdate('Y-m-d H:i:s'),
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ]);
                $productId = (int) $db->insertID();
                $isNew = true;
            }

            $productExists = $productId > 0 && $db->table('products')
                ->where(['id' => $productId, 'tenant_id' => $tenantId])
                ->countAllResults() > 0;
            if (! $productExists) {
                throw new \RuntimeException('Produto inválido.');
            }

            if ($isNew) {
                $file = $this->request->getFile('new_product_image');
                if ($file && $file->isValid() && ! $file->hasMoved()) {
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
            }

            $items->insert([
                'tenant_id' => $tenantId,
                'preorder_id' => $preorderId,
                'product_id' => $productId,
                'quantity' => max(1, (int) $quantity),
                'unit_price' => $this->money($prices[$index]),
                'status' => 'open',
            ]);
        }

        $db->table('preorder_status_history')->insert([
            'tenant_id' => $tenantId,
            'preorder_id' => $preorderId,
            'old_status' => null,
            'new_status' => DeliveryStatus::AWAITING_RELEASE,
            'actor_user_id' => (int) auth()->user()->id,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        (new FinancialEntryService($db))->createForPreorder($tenantId, $preorderId);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new \RuntimeException('Não foi possível salvar a pré-venda.');
        }

        return redirect()
            ->to('/pre-vendas/' . $preorderId)
            ->with('message', 'Pré-venda criada.');
    }

    private function money(mixed $value): string
    {
        return number_format(max(0, (float) str_replace(',', '.', (string) $value)), 2, '.', '');
    }
}
