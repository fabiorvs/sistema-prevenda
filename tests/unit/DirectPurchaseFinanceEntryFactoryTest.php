<?php

declare(strict_types=1);

use App\Modules\Finance\Services\DirectPurchaseFinanceEntryFactory;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DirectPurchaseFinanceEntryFactoryTest extends CIUnitTestCase
{
    public function testCreatesASettledFinancialEntryForADirectPurchase(): void
    {
        $entry = (new DirectPurchaseFinanceEntryFactory())->fromPurchase([
            'id' => 42,
            'purchased_at' => '2026-11-19',
            'quantity' => 2,
            'unit_price' => '75.50',
            'store_name' => 'Feira Diecast',
            'brand' => 'Mini GT',
            'product_name' => 'Nissan Skyline GT-R',
        ]);

        self::assertSame('purchase', $entry['source']);
        self::assertSame('Compra', $entry['source_label']);
        self::assertSame('/compras/42/editar', $entry['source_url']);
        self::assertSame('2026-11-19', $entry['display_date']);
        self::assertSame('Compra: Mini GT Nissan Skyline GT-R', $entry['description']);
        self::assertSame('Feira Diecast', $entry['seller_name']);
        self::assertSame(151.0, $entry['amount']);
        self::assertSame(151.0, $entry['paid_amount']);
        self::assertSame(0.0, $entry['balance']);
        self::assertSame('paid', $entry['display_status']);
    }
}
