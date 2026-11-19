<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

final class DirectPurchaseFinanceEntryFactory
{
    /**
     * @param array{id:int|string,purchased_at:string,quantity:int|string,unit_price:string|int|float,store_name:string,brand:string,product_name:string} $purchase
     * @return array{source:string,source_label:string,source_url:string,due_date:null,display_date:string,description:string,seller_name:string,amount:float,paid_amount:float,balance:float,status:string,display_status:string}
     */
    public function fromPurchase(array $purchase): array
    {
        $amount = (int) $purchase['quantity'] * (float) $purchase['unit_price'];

        return $this->fromSettledExpense(
            'purchase',
            'Compra',
            '/compras/' . $purchase['id'] . '/editar',
            $purchase['purchased_at'],
            'Compra: ' . $purchase['brand'] . ' ' . $purchase['product_name'],
            $purchase['store_name'],
            $amount,
        );
    }

    /**
     * @return array{source:string,source_label:string,source_url:string,due_date:null,display_date:string,description:string,seller_name:string,amount:float,paid_amount:float,balance:float,status:string,display_status:string}
     */
    public function fromSettledExpense(
        string $source,
        string $sourceLabel,
        string $sourceUrl,
        string $date,
        string $description,
        string $sellerName,
        float $amount,
    ): array {
        return [
            'source' => $source,
            'source_label' => $sourceLabel,
            'source_url' => $sourceUrl,
            'due_date' => null,
            'display_date' => $date,
            'description' => $description,
            'seller_name' => $sellerName,
            'amount' => $amount,
            'paid_amount' => $amount,
            'balance' => 0.0,
            'status' => 'paid',
            'display_status' => 'paid',
        ];
    }
}
