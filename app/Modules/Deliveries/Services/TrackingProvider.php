<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Services;

final class TrackingProvider
{
    public const NONE = 'none';
    public const WONCA = 'wonca';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::NONE => 'Não consultar automaticamente',
            self::WONCA => 'Wonca Labs',
        ];
    }

    public static function isValid(string $provider): bool
    {
        return array_key_exists($provider, self::labels());
    }

    /** @return array<string, string> */
    public static function carriersFor(string $provider): array
    {
        return match ($provider) {
            self::WONCA => ['CARRIER_CORREIOS' => 'Correios'],
            default => [],
        };
    }

    public static function supportsCarrier(string $provider, ?string $carrier): bool
    {
        return $provider === self::NONE
            ? $carrier === null || $carrier === ''
            : $carrier !== null && array_key_exists($carrier, self::carriersFor($provider));
    }
}
