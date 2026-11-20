<?php

declare(strict_types=1);

namespace App\Modules\Garage\Services;

use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Garage\Models\GarageItemModel;
use App\Modules\Garage\Models\GaragePaymentModel;
use App\Modules\Sellers\Models\SellerModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;

final class GarageItemService
{
    public function __construct(
        private readonly GarageItemModel $items,
        private readonly GaragePaymentModel $payments,
        private readonly SellerModel $sellers,
        private readonly CatalogService $catalog,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function update(int $tenantId, int $garageItemId, array $data, ?UploadedFile $image): void
    {
        $item = $this->items
            ->where(['id' => $garageItemId, 'tenant_id' => $tenantId])
            ->first();

        if ($item === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $sellerId = (int) ($data['seller_id'] ?? 0);
        if ($this->sellers->where(['id' => $sellerId, 'tenant_id' => $tenantId])->first() === null) {
            throw new \InvalidArgumentException('Vendedor inválido.');
        }

        $unitPrice = $this->normalizeAmount((string) ($data['unit_price'] ?? ''));
        $totalInCents = (int) $item['quantity'] * $this->toCents($unitPrice);
        $paidAmount = (string) ($this->payments
            ->selectSum('amount')
            ->where(['tenant_id' => $tenantId, 'garage_item_id' => $garageItemId])
            ->first()['amount'] ?? '0');

        if ($totalInCents < $this->toCents($paidAmount)) {
            throw new \InvalidArgumentException('O valor não pode ser menor que o já pago.');
        }

        $updated = $this->items->update($garageItemId, [
            'seller_id' => $sellerId,
            'unit_price' => $unitPrice,
            'acquired_at' => (string) $data['acquired_at'],
            'next_payment_date' => ($data['next_payment_date'] ?? '') ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ]);

        if ($updated === false) {
            throw new \RuntimeException('Não foi possível atualizar o item da garagem.');
        }

        if ($image instanceof UploadedFile && $image->getError() !== UPLOAD_ERR_NO_FILE) {
            $this->catalog->replacePrimaryImage($tenantId, (int) $item['product_id'], $image);
        }
    }

    private function normalizeAmount(string $amount): string
    {
        $amount = str_replace(',', '.', trim($amount));
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new \InvalidArgumentException('Informe um valor unitário válido.');
        }

        [$integer, $decimal] = array_pad(explode('.', $amount, 2), 2, '');

        return (string) ((int) $integer) . '.' . str_pad($decimal, 2, '0');
    }

    private function toCents(string $amount): int
    {
        $normalized = $this->normalizeAmount($amount);
        [$integer, $decimal] = explode('.', $normalized, 2);

        return ((int) $integer * 100) + (int) $decimal;
    }
}
