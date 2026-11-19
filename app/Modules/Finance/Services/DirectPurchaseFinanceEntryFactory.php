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

        return [
            'source' => 'purchase',
            'source_label' => 'Compra',
            'source_url' => '/compras/' . $purchase['id'] . '/editar',
            'due_date' => null,
            'display_date' => $purchase['purchased_at'],
            'description' => 'Compra: ' . $purchase['brand'] . ' ' . $purchase['product_name'],
            'seller_name' => $purchase['store_name'],
            'amount' => $amount,
            'paid_amount' => $amount,
            'balance' => 0.0,
            'status' => 'paid',
            'display_status' => 'paid',
        ];
    }
}
