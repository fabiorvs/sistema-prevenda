<?php

declare(strict_types=1);

namespace App\Modules\Explore\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

final class ExploreController extends BaseController
{
    public function search(): string
    {
        $q = trim((string) $this->request->getGet('q')); $tenantId = $this->tenant(); $db = db_connect(); $results = [];
        if ($q !== '') {
            foreach ($db->table('products')->select('id, name, brand')->where('tenant_id', $tenantId)->groupStart()->like('name', $q)->orLike('brand', $q)->groupEnd()->limit(12)->get()->getResultArray() as $row) $results[] = ['type' => 'Miniatura', 'title' => $row['brand'] . ' — ' . $row['name'], 'url' => '/carrinhos/' . $row['id'] . '/editar'];
            foreach ($db->table('sellers')->select('id, name')->where('tenant_id', $tenantId)->like('name', $q)->limit(12)->get()->getResultArray() as $row) $results[] = ['type' => 'Vendedor', 'title' => $row['name'], 'url' => '/vendedores/' . $row['id'] . '/editar'];
            foreach ($db->table('preorders')->select('id, reference')->where('tenant_id', $tenantId)->like('reference', $q)->limit(12)->get()->getResultArray() as $row) $results[] = ['type' => 'Pré-venda', 'title' => $row['reference'], 'url' => '/pre-vendas/' . $row['id']];
            foreach ($db->table('garage_items')->select('garage_items.id, products.name, products.brand')->join('products', 'products.id = garage_items.product_id')->where('garage_items.tenant_id', $tenantId)->groupStart()->like('products.name', $q)->orLike('products.brand', $q)->groupEnd()->limit(12)->get()->getResultArray() as $row) $results[] = ['type' => 'Garagem', 'title' => $row['brand'] . ' — ' . $row['name'], 'url' => '/garagem/' . $row['id']];
            foreach ($db->table('direct_purchases')->select('direct_purchases.id, direct_purchases.store_name, products.name')->join('products', 'products.id = direct_purchases.product_id')->where('direct_purchases.tenant_id', $tenantId)->groupStart()->like('products.name', $q)->orLike('direct_purchases.store_name', $q)->groupEnd()->limit(12)->get()->getResultArray() as $row) $results[] = ['type' => 'Compra', 'title' => $row['name'] . ' · ' . $row['store_name'], 'url' => '/compras'];
        }
        return view('explore/search', ['q' => $q, 'results' => $results]);
    }

    public function calendar(): string
    {
        $month = (string) $this->request->getGet('month'); if (! preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
        $from = $month . '-01'; $to = date('Y-m-t', strtotime($from)); $tenantId = $this->tenant(); $db = db_connect(); $events = [];
        foreach ($db->table('financial_entries')->select("financial_entries.due_date AS date, financial_entries.description AS title, 'Pagamento' AS type, financial_entries.id", false)->where('tenant_id', $tenantId)->whereIn('status', ['pending', 'partial'])->where('due_date >=', $from)->where('due_date <=', $to)->get()->getResultArray() as $row) $events[] = $row + ['url' => '/financeiro/contas/' . $row['id'] . '/pagamento'];
        foreach ($db->table('shipments')->select("expected_delivery_date AS date, reference AS title, 'Entrega' AS type, id", false)->where('tenant_id', $tenantId)->whereIn('status', ['organized', 'shipped'])->where('expected_delivery_date >=', $from)->where('expected_delivery_date <=', $to)->get()->getResultArray() as $row) $events[] = $row + ['url' => '/entregas/envios/' . $row['id']];
        foreach ($db->table('preorders')->select("expected_delivery_date AS date, reference AS title, 'Chegada ao vendedor' AS type, id", false)->where('tenant_id', $tenantId)->whereNotIn('status', ['received', 'delivered', 'canceled'])->where('expected_delivery_date >=', $from)->where('expected_delivery_date <=', $to)->get()->getResultArray() as $row) $events[] = $row + ['url' => '/pre-vendas/' . $row['id']];
        usort($events, static fn (array $a, array $b): int => strcmp($a['date'], $b['date']));
        return view('explore/calendar', ['month' => $month, 'events' => $events]);
    }
    private function tenant(): int { return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id); }
}
