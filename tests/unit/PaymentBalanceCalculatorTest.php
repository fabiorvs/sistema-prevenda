<?php

use App\Modules\Finance\Services\PaymentBalanceCalculator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Caracteriza as invariantes de saldo exibidas pelo fluxo atual de pagamentos.
 *
 * @internal
 */
final class PaymentBalanceCalculatorTest extends CIUnitTestCase
{
    public function testCalculatesPartialBalanceWithoutFloatingPointTolerance(): void
    {
        $calculator = new PaymentBalanceCalculator();

        $remaining = $calculator->remainingInCents(10_000, 2_500);

        $this->assertSame(7_500, $remaining);
        $this->assertSame('partial', $calculator->statusFor(10_000, 2_500));
    }

    public function testMarksEntryAsPaidWhenAllocationsReachItsAmount(): void
    {
        $calculator = new PaymentBalanceCalculator();

        $this->assertSame('paid', $calculator->statusFor(10_000, 10_000));
        $this->assertSame(0, $calculator->remainingInCents(10_000, 12_000));
    }

    public function testRejectsPaymentAboveRemainingBalance(): void
    {
        $calculator = new PaymentBalanceCalculator();

        $this->expectException(DomainException::class);
        $calculator->assertDoesNotExceedRemaining(7_501, 7_500);
    }
}
