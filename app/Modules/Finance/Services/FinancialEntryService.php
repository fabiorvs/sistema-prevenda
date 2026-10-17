<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Repositories\FinancialEntryRepository;

final class FinancialEntryService
{
    public function __construct(private readonly FinancialEntryRepository $entries)
    {
    }

    public function createForPreorder(int $tenantId, int $preorderId): void
    {
        $this->syncForPreorder($tenantId, $preorderId);
    }

    public function syncForPreorder(int $tenantId, int $preorderId): void
    {
        $preorder = $this->entries->preorderForTenant($tenantId, $preorderId);

        if ($preorder === null) {
            throw new \RuntimeException('Pré-venda não encontrada para a conta ativa.');
        }

        $itemsTotal = $this->entries->itemsTotal($tenantId, $preorderId);
        $amount = max(
            0,
            (float) $itemsTotal
                + (float) $preorder['shipping_amount']
                - (float) $preorder['discount_amount']
                - (float) $preorder['down_payment_amount'],
        );

        $entry = $this->entries->findForPreorder($tenantId, $preorderId);

        if ($entry === null) {
            if ($amount <= 0.0001) {
                return;
            }

            $now = gmdate('Y-m-d H:i:s');
            $this->entries->create([
                'tenant_id' => $tenantId,
                'preorder_id' => $preorderId,
                'description' => 'Saldo da pré-venda ' . $preorder['reference'],
                'due_date' => $preorder['expected_delivery_date'],
                'amount' => number_format($amount, 2, '.', ''),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $allocated = (float) $this->entries->allocatedAmount($tenantId, (int) $entry['id']);
        $status = match (true) {
            $allocated >= $amount - 0.0001 => 'paid',
            $allocated > 0 => 'partial',
            default => 'pending',
        };

        $this->entries->updateForTenant($tenantId, (int) $entry['id'], [
                'due_date' => $preorder['expected_delivery_date'],
                'amount' => number_format($amount, 2, '.', ''),
                'status' => $status,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ]);
    }
}
