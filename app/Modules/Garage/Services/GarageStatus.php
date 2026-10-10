<?php

declare(strict_types=1);

namespace App\Modules\Garage\Services;

final class GarageStatus
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'stored' => 'Guardado no vendedor',
            'prepared' => 'Envio preparado',
            'shipping' => 'Em transporte',
            'received' => 'Recebido',
            'canceled' => 'Cancelado',
        ];
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::labels());
    }
}
