<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Controllers;

use App\Controllers\BaseController;
use App\Modules\Deliveries\Services\ShipmentService;
use App\Modules\Deliveries\Services\ShipmentStatus;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;

final class ShipmentsController extends BaseController
{
    public function create(): string
    {
        $tenantId = $this->tenant();
        $sellerId = (int) $this->request->getGet('seller_id');
        $db = db_connect();

        return view('deliveries/shipment_form', [
            'sellers' => $db->table('sellers')
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get()
                ->getResultArray(),
            'sellerId' => $sellerId,
            'items' => $this->service()->eligibleItems($tenantId, $sellerId),
        ]);
    }

    public function store()
    {
        $rules = [
            'seller_id' => 'required|is_natural_no_zero',
            'carrier' => 'required|max_length[120]',
            'tracking_code' => 'permit_empty|max_length[120]',
            'shipping_amount' => 'required|decimal|greater_than_equal_to[0]',
            'freight_split' => 'required|in_list[quantity,value]',
            'shipped_at' => 'permit_empty|valid_date[Y-m-d]',
            'expected_delivery_date' => 'permit_empty|valid_date[Y-m-d]',
            'notes' => 'permit_empty|max_length[2000]',
            'quantity' => 'required',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $shipmentId = $this->service()->create(
                $this->tenant(),
                (int) $this->request->getPost('seller_id'),
                (array) $this->request->getPost('quantity'),
                $this->request->getPost([
                    'carrier',
                    'tracking_code',
                    'shipping_amount',
                    'freight_split',
                    'shipped_at',
                    'expected_delivery_date',
                    'notes',
                ]),
            );
        } catch (\InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('errors', ['items' => $exception->getMessage()]);
        }

        return redirect()->to('/entregas/envios/' . $shipmentId)->with('message', 'Envio agrupado organizado.');
    }

    public function show(int $id): string
    {
        $shipment = $this->service()->find($this->tenant(), $id);
        if ($shipment === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('deliveries/shipment_show', [
            'shipment' => $shipment,
            'labels' => ShipmentStatus::labels(),
        ]);
    }

    public function updateStatus(int $id)
    {
        $rules = [
            'status' => 'required|in_list[organized,shipped,received,canceled]',
            'tracking_code' => 'permit_empty|max_length[120]',
            'shipped_at' => 'permit_empty|valid_date[Y-m-d]',
            'expected_delivery_date' => 'permit_empty|valid_date[Y-m-d]',
            'received_at' => 'permit_empty|valid_date[Y-m-d]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->service()->updateStatus(
                $this->tenant(),
                $id,
                (string) $this->request->getPost('status'),
                $this->request->getPost(['tracking_code', 'shipped_at', 'expected_delivery_date', 'received_at']),
            );
        } catch (\InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('errors', ['status' => $exception->getMessage()]);
        }

        return redirect()->to('/entregas/envios/' . $id)->with('message', 'Envio atualizado.');
    }

    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))
            ->forUser((int) auth()->user()->id);
    }

    private function service(): ShipmentService
    {
        return new ShipmentService(db_connect());
    }
}
