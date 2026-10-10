<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Services;

final class ShipmentStatus
{
    public const ORGANIZED = 'organized';
    public const SHIPPED = 'shipped';
    public const RECEIVED = 'received';
    public const CANCELED = 'canceled';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::ORGANIZED => 'Organizado',
            self::SHIPPED => 'Enviado',
            self::RECEIVED => 'Recebido',
            self::CANCELED => 'Cancelado',
        ];
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::labels());
    }
}
