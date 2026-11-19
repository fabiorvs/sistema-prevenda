<?php

declare(strict_types=1);

namespace App\Modules\Purchases\Services;

use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Purchases\Repositories\PurchaseRepository;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;

final class PurchaseService
{
    private readonly PurchaseRepository $purchases;

    public function __construct(private readonly ConnectionInterface $db, private readonly CatalogService $catalog)
    {
        $this->purchases = new PurchaseRepository($db);
    }

    /** @return list<array<string,mixed>> */
    public function all(int $tenantId): array
    {
        return $this->purchases->all($tenantId);
    }

    /** @return array{products:list<array<string,mixed>>,brands:list<array<string,mixed>>} */
    public function formData(int $tenantId): array
    {
        return $this->purchases->formData($tenantId);
    }

    /** @return array<string, mixed> */
    public function find(int $tenantId, int $purchaseId): array
    {
        $purchase = $this->purchases->findForTenant($tenantId, $purchaseId);
        if ($purchase === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $purchase;
    }

    /** @param array<string,mixed> $data */
    public function register(int $tenantId, array $data, ?UploadedFile $image): void
    {
        $this->db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        $productId = $this->resolveProduct($tenantId, $data, $image, $now);
        $purchaseData = $this->purchaseData($data, $productId, $now);
        $purchaseId = $this->purchases->createPurchase([
            'tenant_id' => $tenantId,
            'created_at' => $now,
        ] + $purchaseData);
        $this->purchases->createCollectionItem([
            'tenant_id' => $tenantId,
            'direct_purchase_id' => $purchaseId,
            'product_id' => $productId,
            'quantity' => $purchaseData['quantity'],
            'unit_cost' => $purchaseData['unit_price'],
            'freight_amount' => 0,
            'received_at' => $purchaseData['purchased_at'],
            'location' => $purchaseData['location'],
            'notes' => $purchaseData['notes'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Não foi possível registrar a compra.');
        }
    }

    /** @param array<string, mixed> $data */
    public function update(int $tenantId, int $purchaseId, array $data, ?UploadedFile $image): void
    {
        $this->find($tenantId, $purchaseId);
        $this->db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        $productId = $this->resolveProduct($tenantId, $data, $image, $now);
        $purchaseData = $this->purchaseData($data, $productId, $now);

        $this->purchases->updateForTenant($tenantId, $purchaseId, $purchaseData);
        $this->purchases->updateCollectionForPurchase($tenantId, $purchaseId, [
            'product_id' => $productId,
            'quantity' => $purchaseData['quantity'],
            'unit_cost' => $purchaseData['unit_price'],
            'received_at' => $purchaseData['purchased_at'],
            'location' => $purchaseData['location'],
            'notes' => $purchaseData['notes'],
            'updated_at' => $now,
        ]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Não foi possível atualizar a compra.');
        }
    }

    /** @param array<string, mixed> $data */
    private function resolveProduct(int $tenantId, array $data, ?UploadedFile $image, string $now): int
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $name = trim((string) ($data['new_product_name'] ?? ''));
        if ($productId === 0 && $name === '') {
            throw new \InvalidArgumentException('Informe o modelo da miniatura.');
        }

        if ($productId === 0) {
            $brand = trim((string) ($data['new_product_brand'] ?? ''));
            if ($brand === '__new__') {
                $brand = trim((string) ($data['custom_product_brand'] ?? ''));
            }
            $productId = $this->purchases->createProduct([
                'tenant_id' => $tenantId,
                'brand' => $brand ?: 'Sem marca',
                'name' => $name,
                'scale' => trim((string) ($data['new_product_scale'] ?? '')) ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $this->catalog->find($tenantId, $productId);
        }

        if ($image instanceof UploadedFile && $image->getError() !== UPLOAD_ERR_NO_FILE) {
            $this->catalog->replacePrimaryImage($tenantId, $productId, $image);
        }

        return $productId;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function purchaseData(array $data, int $productId, string $updatedAt): array
    {
        return [
            'product_id' => $productId,
            'store_name' => trim((string) $data['store_name']),
            'purchased_at' => (string) $data['purchased_at'],
            'quantity' => (int) $data['quantity'],
            'unit_price' => $this->normalizeAmount((string) $data['unit_price']),
            'location' => trim((string) ($data['location'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'updated_at' => $updatedAt,
        ];
    }

    private function normalizeAmount(string $amount): string
    {
        $amount = str_replace(',', '.', trim($amount));
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new \InvalidArgumentException('Informe um valor unitário válido.');
        }

        [$integer, $decimal] = array_pad(explode('.', $amount, 2), 2, '');

        return $integer . '.' . str_pad($decimal, 2, '0');
    }
}
