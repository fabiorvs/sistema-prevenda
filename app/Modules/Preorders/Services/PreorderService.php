<?php

declare(strict_types=1);

namespace App\Modules\Preorders\Services;

use App\Infrastructure\Database\TransactionManager;
use App\Modules\Deliveries\Services\DeliveryStatus;
use App\Modules\Preorders\Repositories\PreorderRepository;
use CodeIgniter\Exceptions\PageNotFoundException;

final class PreorderService
{
    public function __construct(
        private readonly PreorderRepository $preorders,
        private readonly TransactionManager $transactions,
    ) {
    }

    public function cancel(int $tenantId, int $preorderId, int $actorUserId): bool
    {
        $preorder = $this->preorders->findForTenant($tenantId, $preorderId);
        if ($preorder === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($preorder['status'] === DeliveryStatus::CANCELED) {
            return false;
        }

        $this->transactions->run(function () use ($tenantId, $preorderId, $actorUserId, $preorder): void {
            $updated = $this->preorders->updateForTenant($tenantId, $preorderId, [
                'status' => DeliveryStatus::CANCELED,
            ]);
            if (! $updated) {
                throw new \RuntimeException('Não foi possível cancelar a pré-venda.');
            }

            $this->preorders->addHistory($tenantId, $preorderId, [
                'old_status' => $preorder['status'],
                'new_status' => DeliveryStatus::CANCELED,
                'actor_user_id' => $actorUserId,
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
        });

        return true;
    }
}
