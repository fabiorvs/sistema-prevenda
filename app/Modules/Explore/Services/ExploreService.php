<?php

declare(strict_types=1);

namespace App\Modules\Explore\Services;

use App\Modules\Explore\Repositories\ExploreRepository;

final class ExploreService
{
    public function __construct(private readonly ExploreRepository $explore)
    {
    }

    /** @return list<array{type:string,title:string,url:string}> */
    public function search(int $tenantId, string $query): array
    {
        if ($query === '') {
            return [];
        }

        $results = [];

        foreach ($this->explore->searchProducts($tenantId, $query) as $row) {
            $results[] = ['type' => 'Miniatura', 'title' => $row['brand'] . ' — ' . $row['name'], 'url' => '/carrinhos/' . $row['id'] . '/editar'];
        }
        foreach ($this->explore->searchSellers($tenantId, $query) as $row) {
            $results[] = ['type' => 'Vendedor', 'title' => $row['name'], 'url' => '/vendedores/' . $row['id'] . '/editar'];
        }
        foreach ($this->explore->searchPreorders($tenantId, $query) as $row) {
            $results[] = ['type' => 'Pré-venda', 'title' => $row['reference'], 'url' => '/pre-vendas/' . $row['id']];
        }
        foreach ($this->explore->searchGarageItems($tenantId, $query) as $row) {
            $results[] = ['type' => 'Garagem', 'title' => $row['brand'] . ' — ' . $row['name'], 'url' => '/garagem/' . $row['id']];
        }
        foreach ($this->explore->searchPurchases($tenantId, $query) as $row) {
            $results[] = ['type' => 'Compra', 'title' => $row['name'] . ' · ' . $row['store_name'], 'url' => '/compras'];
        }

        return $results;
    }

    /** @return list<array{date:string,title:string,type:string,id:int|string,url:string}> */
    public function calendarEvents(int $tenantId, string $month): array
    {
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        $events = [];

        foreach ($this->explore->calendarEvents($tenantId, $from, $to) as $event) {
            $events[] = [
                'date' => (string) $event['date'],
                'title' => (string) $event['title'],
                'type' => (string) $event['type'],
                'id' => $event['id'],
                'url' => $this->eventUrl((string) $event['source'], $event['id']),
            ];
        }

        usort($events, static fn (array $left, array $right): int => strcmp($left['date'], $right['date']));

        return $events;
    }

    private function eventUrl(string $source, int|string $id): string
    {
        return match ($source) {
            'financial_entry' => '/financeiro/contas/' . $id . '/pagamento',
            'shipment' => '/entregas/envios/' . $id,
            'preorder' => '/pre-vendas/' . $id,
            'garage' => '/garagem/' . $id,
            'purchase' => '/compras',
            'import' => '/importacoes',
            default => '/dashboard',
        };
    }
}
