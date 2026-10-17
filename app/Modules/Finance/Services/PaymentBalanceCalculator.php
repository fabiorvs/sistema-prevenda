<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use DomainException;

final class PaymentBalanceCalculator
{
    public function remainingInCents(int $entryAmountInCents, int $allocatedAmountInCents): int
    {
        return max(0, $entryAmountInCents - $allocatedAmountInCents);
    }

    public function statusFor(int $entryAmountInCents, int $allocatedAmountInCents): string
    {
        if ($allocatedAmountInCents <= 0) {
            return 'pending';
        }

        return $allocatedAmountInCents >= $entryAmountInCents ? 'paid' : 'partial';
    }

    public function assertDoesNotExceedRemaining(int $amountInCents, int $remainingInCents): void
    {
        if ($amountInCents <= 0) {
            throw new DomainException('O pagamento deve ser maior que zero.');
        }

        if ($amountInCents > $remainingInCents) {
            throw new DomainException('O pagamento excede o saldo da conta.');
        }
    }
}
