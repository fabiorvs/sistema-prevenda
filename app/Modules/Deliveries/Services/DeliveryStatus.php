<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Services;

final class DeliveryStatus
{
    public const RESERVED = 'reserved';
    public const AWAITING_RELEASE = 'awaiting_release';
    public const AWAITING_SHIPMENT = 'awaiting_shipment';
    public const IN_TRANSIT = 'in_transit';
    public const PARTIALLY_RECEIVED = 'partially_received';
    public const RECEIVED = 'received';
    public const CANCELED = 'canceled';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::RESERVED => 'Reservado',
            self::AWAITING_RELEASE => 'Aguardando lançamento',
            self::AWAITING_SHIPMENT => 'Aguardando envio',
            self::IN_TRANSIT => 'Em transporte',
            self::PARTIALLY_RECEIVED => 'Parcialmente recebido',
            self::RECEIVED => 'Recebido',
            self::CANCELED => 'Cancelado',
        ];
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::labels());
    }

    /**
     * @param list<array{quantity:int|string, received_quantity:int|string}> $items
     */
    public static function fromItems(array $items): string
    {
        $total = 0;
        $received = 0;

        foreach ($items as $item) {
            $total += (int) $item['quantity'];
            $received += (int) $item['received_quantity'];
        }

        if ($total > 0 && $received >= $total) {
            return self::RECEIVED;
        }

        if ($received > 0) {
            return self::PARTIALLY_RECEIVED;
        }

        return self::AWAITING_RELEASE;
    }

    public static function isFinal(string $status): bool
    {
        return in_array($status, [self::RECEIVED, self::CANCELED], true);
    }
}
