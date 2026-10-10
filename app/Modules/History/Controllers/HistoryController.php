<?php

declare(strict_types=1);

namespace App\Modules\History\Controllers;

use App\Controllers\BaseController;
use App\Modules\Deliveries\Services\DeliveryStatus;
use App\Modules\Deliveries\Services\ShipmentService;
use App\Modules\Deliveries\Services\ShipmentStatus;
use App\Modules\Garage\Services\GarageStatus;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class HistoryController extends BaseController
{
    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }

    public function index(): string
    {
        $tenantId = $this->tenant();
        $db = db_connect();

        $preorders = $db->table('preorders')
            ->select('preorders.*, sellers.name AS seller_name, COALESCE(SUM(preorder_items.quantity * preorder_items.unit_price), 0) AS total', false)
            ->join('sellers', 'sellers.id = preorders.seller_id')
            ->join('preorder_items', 'preorder_items.preorder_id = preorders.id', 'left')
            ->where('preorders.tenant_id', $tenantId)
            ->whereIn('preorders.status', [
                DeliveryStatus::AWAITING_SHIPMENT,
                DeliveryStatus::IN_TRANSIT,
                DeliveryStatus::DELIVERED,
                DeliveryStatus::CANCELED,
            ])
            ->groupBy('preorders.id')
            ->orderBy('preorders.updated_at', 'DESC')
            ->get()
            ->getResultArray();

        $garageItems = $db->table('garage_items')
            ->select('garage_items.*, sellers.name AS seller_name, products.name AS product_name, products.brand, garage_items.quantity * garage_items.unit_price AS total', false)
            ->join('sellers', 'sellers.id = garage_items.seller_id')
            ->join('products', 'products.id = garage_items.product_id')
            ->where('garage_items.tenant_id', $tenantId)
            ->where('garage_items.status !=', 'stored')
            ->orderBy('garage_items.updated_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('history/index', [
            'preorders' => $preorders,
            'garageItems' => $garageItems,
            'shipments' => (new ShipmentService($db))->all($tenantId),
            'preorderLabels' => DeliveryStatus::labels(),
            'garageLabels' => GarageStatus::labels(),
            'shipmentLabels' => ShipmentStatus::labels(),
        ]);
    }
}
