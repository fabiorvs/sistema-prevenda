<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class CorrectGarageInitialPaymentDates extends BaseCommand
{
    protected $group = 'Garage';
    protected $name = 'garage:correct-initial-payment-dates';
    protected $description = 'Alinha a data de pagamentos iniciais da Garagem à data de compra do item.';
    protected $usage = 'garage:correct-initial-payment-dates [--apply]';
    protected $options = [
        '--apply' => 'Aplica a correção. Sem esta opção, apenas exibe os pagamentos que seriam alterados.',
    ];

    public function run(array $params): void
    {
        $db = db_connect();
        $payments = $db->table('garage_payments')
            ->select('garage_payments.id, garage_payments.garage_item_id, garage_payments.paid_at, garage_items.acquired_at, products.name AS product_name, sellers.name AS seller_name')
            ->join('garage_items', 'garage_items.id = garage_payments.garage_item_id')
            ->join('products', 'products.id = garage_items.product_id')
            ->join('sellers', 'sellers.id = garage_items.seller_id')
            ->where('garage_payments.notes', 'Pagamento inicial')
            ->where('DATE(garage_payments.paid_at) != garage_items.acquired_at', null, false)
            ->orderBy('garage_payments.id')
            ->get()
            ->getResultArray();

        if ($payments === []) {
            CLI::write('Nenhum pagamento inicial com data divergente foi encontrado.', 'green');

            return;
        }

        CLI::write('Pagamentos iniciais que serão alinhados à data da compra:', 'yellow');
        CLI::table(
            array_map(static fn (array $payment): array => [
                $payment['id'],
                $payment['product_name'],
                $payment['seller_name'],
                date('d/m/Y', strtotime($payment['paid_at'])),
                date('d/m/Y', strtotime($payment['acquired_at'])),
            ], $payments),
            ['Pagamento', 'Miniatura', 'Vendedor', 'Data atual', 'Data da compra']
        );

        if (! CLI::getOption('apply')) {
            CLI::write('Prévia somente. Para aplicar a correção, execute com --apply.', 'yellow');

            return;
        }

        $db->transStart();
        $db->query(
            "UPDATE garage_payments
             INNER JOIN garage_items ON garage_items.id = garage_payments.garage_item_id
             SET garage_payments.paid_at = CONCAT(garage_items.acquired_at, ' ', TIME(garage_payments.paid_at)),
                 garage_payments.updated_at = NOW()
             WHERE garage_payments.notes = 'Pagamento inicial'
               AND DATE(garage_payments.paid_at) != garage_items.acquired_at"
        );
        $db->transComplete();

        if (! $db->transStatus()) {
            CLI::error('Não foi possível corrigir os pagamentos. Nenhuma alteração foi confirmada.');

            return;
        }

        CLI::write(count($payments) . ' pagamento(s) inicial(is) corrigido(s).', 'green');
    }
}
