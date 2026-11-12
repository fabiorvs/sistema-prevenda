<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Services;

interface ShipmentTrackingClient
{
    /** @return array<string, mixed> */
    public function track(string $provider, string $code, string $carrier): array;
}
