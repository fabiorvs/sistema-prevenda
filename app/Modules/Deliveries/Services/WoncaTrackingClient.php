<?php

declare(strict_types=1);

namespace App\Modules\Deliveries\Services;

use CodeIgniter\HTTP\CURLRequest;

final class WoncaTrackingClient implements ShipmentTrackingClient
{
    private const TRACK_URL = 'https://api-labs.wonca.com.br/wonca.labs.v1.LabsService/Track';

    public function __construct(private readonly CURLRequest $http, private readonly string $apiKey)
    {
    }

    public function track(string $provider, string $code, string $carrier): array
    {
        if ($provider !== TrackingProvider::WONCA) {
            throw new \InvalidArgumentException('Serviço de rastreio não suportado.');
        }
        if ($this->apiKey === '') {
            throw new \RuntimeException('A chave WONKA_KEY não está configurada.');
        }

        try {
            $response = $this->http->post(self::TRACK_URL, [
                'headers' => [
                    'Authorization' => 'Apikey ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['code' => $code, 'carrier' => $carrier],
                'timeout' => 15,
                'http_errors' => false,
            ]);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Não foi possível consultar o serviço de rastreio.', 0, $exception);
        }

        $body = $response->getBody();
        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            $payload = ['raw_response' => mb_substr($body, 0, 60000)];
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new \RuntimeException('O serviço de rastreio recusou a consulta (' . $response->getStatusCode() . ').');
        }

        return $payload;
    }
}
