<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Repositories\InvestmentEntryRepository;

final class InvestmentEntryService
{
    public function __construct(
        private readonly InvestmentEntryRepository $entries,
        private readonly DirectPurchaseFinanceEntryFactory $entryFactory,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listForPeriod(int $tenantId, string $from, string $to): array
    {
        $entries = [];

        foreach ($this->entries->confirmedPreorderPaymentsForPeriod($tenantId, $from, $to) as $payment) {
            $amount = (float) $payment['amount'];
            if ($payment['type'] === 'refund') {
                $amount *= -1;
            }

            $description = $payment['type'] === 'refund'
                ? 'Estorno da pré-venda ' . $payment['reference']
                : 'Pagamento da pré-venda ' . $payment['reference'];

            $entries[] = $this->entryFactory->fromSettledExpense(
                'preorder_payment',
                (string) $payment['reference'],
                '/pre-vendas/' . $payment['preorder_id'],
                (string) $payment['paid_date'],
                $description,
                (string) $payment['seller_name'],
                $amount,
            );
        }

        foreach ($this->entries->preorderDownPaymentsForPeriod($tenantId, $from, $to) as $preorder) {
            $entries[] = $this->entryFactory->fromSettledExpense(
                'preorder_down_payment',
                (string) $preorder['reference'],
                '/pre-vendas/' . $preorder['id'],
                (string) $preorder['order_date'],
                'Sinal da pré-venda ' . $preorder['reference'],
                (string) $preorder['seller_name'],
                (float) $preorder['down_payment_amount'],
            );
        }

        foreach ($this->entries->garagePaymentsForPeriod($tenantId, $from, $to) as $payment) {
            $entries[] = $this->entryFactory->fromSettledExpense(
                'garage_payment',
                'Garagem',
                '/garagem/' . $payment['garage_item_id'],
                (string) $payment['paid_date'],
                'Pagamento da Garagem: ' . $payment['brand'] . ' ' . $payment['product_name'],
                (string) $payment['seller_name'],
                (float) $payment['amount'],
            );
        }

        foreach ($this->entries->shipmentsForPeriod($tenantId, $from, $to) as $shipment) {
            $sellerName = (string) ($shipment['seller_name'] ?: $shipment['carrier']);

            $entries[] = $this->entryFactory->fromSettledExpense(
                'shipment',
                'Frete',
                '/entregas/envios/' . $shipment['id'],
                (string) $shipment['shipped_at'],
                'Frete do envio ' . $shipment['reference'],
                $sellerName,
                (float) $shipment['shipping_amount'],
            );
        }

        foreach ($this->entries->directPurchasesForPeriod($tenantId, $from, $to) as $purchase) {
            $entries[] = $this->entryFactory->fromPurchase($purchase);
        }

        foreach ($this->entries->importsForPeriod($tenantId, $from, $to) as $import) {
            $amount = (
                (float) $import['item_amount']
                + (float) $import['shipping_amount']
                + (float) $import['tax_amount']
            ) * (float) $import['exchange_rate'];

            $entries[] = $this->entryFactory->fromSettledExpense(
                'import',
                'Importação',
                '/importacoes',
                (string) $import['purchased_at'],
                'Importação: ' . $import['brand'] . ' ' . $import['product_name'],
                (string) $import['supplier_name'],
                $amount,
            );
        }

        return $entries;
    }
}
