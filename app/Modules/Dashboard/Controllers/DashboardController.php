<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Controllers\BaseController;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

class DashboardController extends BaseController
{
    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }

    public function index(): string
    {
        $filters = $this->filters();
        $report = (new DashboardService(db_connect()))->report($this->tenant(), $filters['seller_id'], $filters['from'], $filters['to']);
        $sellers = db_connect()->table('sellers')->where('tenant_id', $this->tenant())->orderBy('name')->get()->getResultArray();

        return view('dashboard/index', $report + ['filters' => $filters, 'sellers' => $sellers]);
    }

    public function export(): \CodeIgniter\HTTP\ResponseInterface
    {
        $filters = $this->filters();
        $report = (new DashboardService(db_connect()))->report($this->tenant(), $filters['seller_id'], $filters['from'], $filters['to']);
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Referência', 'Vendedor', 'Pedido', 'Entrega prevista', 'Total', 'Pago', 'Saldo'], ';');
        foreach ($report['orders'] as $order) {
            fputcsv($output, [$order['reference'], $order['seller_name'], $order['order_date'], $order['expected_delivery_date'], number_format((float) $order['total'], 2, ',', '.'), number_format((float) $order['paid'], 2, ',', '.'), number_format((float) $order['balance'], 2, ',', '.')], ';');
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $this->response->download('pre-vendas.csv', $csv)->setHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @return array{seller_id:?int, from:?string, to:?string} */
    private function filters(): array
    {
        $sellerId = (int) $this->request->getGet('seller_id');
        $from = $this->request->getGet('from') ?: null;
        $to = $this->request->getGet('to') ?: null;
        return ['seller_id' => $sellerId > 0 ? $sellerId : null, 'from' => $from, 'to' => $to];
    }
}
