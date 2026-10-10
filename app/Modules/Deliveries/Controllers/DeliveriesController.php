<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Controllers;

use App\Controllers\BaseController;
use App\Modules\Deliveries\Services\DeliveryService;
use App\Modules\Deliveries\Services\DeliveryStatus;
use App\Modules\Deliveries\Services\ShipmentService;
use App\Modules\Deliveries\Services\ShipmentStatus;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;

final class DeliveriesController extends BaseController
{
    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }

    public function index(): string
    {
        $tenantId = $this->tenant();
        $status = (string) $this->request->getGet('status');
        $late = $this->request->getGet('late') === '1';
        $query = db_connect()
            ->table('preorders')
            ->select('preorders.*, sellers.name AS seller_name, SUM(preorder_items.quantity) AS total_quantity, SUM(preorder_items.received_quantity) AS received_quantity', false)
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->join('preorder_items', 'preorder_items.preorder_id = preorders.id')
            ->where('preorders.tenant_id', $tenantId)
            ->where('preorders.status !=', DeliveryStatus::CANCELED)
            ->groupBy('preorders.id')
            ->having('SUM(preorder_items.received_quantity) < SUM(preorder_items.quantity)', null, false)
            ->orderBy('preorders.expected_delivery_date IS NULL', 'ASC', false)
            ->orderBy('preorders.expected_delivery_date', 'ASC');

        if (DeliveryStatus::isValid($status)) {
            $query->where('preorders.status', $status);
        }

        if ($late) {
            $query
                ->where('preorders.expected_delivery_date <', date('Y-m-d'))
                ->whereNotIn('preorders.status', [DeliveryStatus::DELIVERED, DeliveryStatus::CANCELED]);
        }

        return view('deliveries/index', [
            'orders' => $query->get()->getResultArray(),
            'labels' => DeliveryStatus::labels(),
            'filters' => ['status' => $status, 'late' => $late],
            'shipments' => (new ShipmentService(db_connect()))->all($tenantId),
            'shipmentLabels' => ShipmentStatus::labels(),
        ]);
    }

    public function show(int $id): string
    {
        return view('deliveries/show', [
            'order' => $this->order($id),
            'labels' => DeliveryStatus::labels(),
        ]);
    }

    public function receive(int $id)
    {
        $rules = [
            'expected_delivery_date' => 'permit_empty|valid_date[Y-m-d]',
            'actual_delivery_date' => 'permit_empty|valid_date[Y-m-d]',
            'tracking_code' => 'permit_empty|max_length[120]',
            'notes' => 'permit_empty|max_length[2000]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->service()->receiveItems(
                $this->tenant(),
                $id,
                (int) auth()->user()->id,
                (array) $this->request->getPost('received'),
                $this->request->getPost(['tracking_code', 'expected_delivery_date', 'actual_delivery_date', 'notes']),
            );
        } catch (\InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('errors', ['received' => $exception->getMessage()]);
        }

        return redirect()->to('/entregas')->with('message', 'Chegada ao vendedor registrada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function order(int $id): array
    {
        $order = $this->service()->findOrder($this->tenant(), $id);
        if ($order === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $order;
    }

    private function service(): DeliveryService
    {
        return new DeliveryService(db_connect());
    }
}
