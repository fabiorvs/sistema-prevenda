<?= $this->include('partials/header') ?>

<?php
$badgeClass = static fn (string $status): string => match ($status) {
    'canceled' => 'text-bg-danger',
    'delivered', 'received' => 'text-bg-success',
    'in_transit', 'shipping', 'shipped' => 'text-bg-primary',
    default => 'text-bg-warning',
};
?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <h1 class="dc-page-title">Histórico</h1>
            <p class="dc-page-subtitle">Acompanhe compras que já saíram do fluxo ativo e todos os seus envios.</p>
        </div>
        <a class="btn btn-outline-primary" href="/entregas">
            <i class="bi bi-box-seam"></i> Ver entregas
        </a>
    </div>

    <section class="card mb-4">
        <div class="card-body border-bottom">
            <h2 class="dc-panel-title mb-1">Histórico de compras</h2>
            <p class="dc-list-meta mb-0">Pré-vendas incluídas em envio, entregues ou canceladas.</p>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Referência</th><th>Vendedor</th><th>Valor</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($preorders as $preorder): ?>
                        <tr>
                            <td><strong><?= esc($preorder['reference']) ?></strong></td>
                            <td><?= esc($preorder['seller_name']) ?></td>
                            <td>R$ <?= number_format((float) $preorder['total'], 2, ',', '.') ?></td>
                            <td><span class="badge <?= $badgeClass($preorder['status']) ?>"><?= esc($preorderLabels[$preorder['status']] ?? $preorder['status']) ?></span></td>
                            <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/pre-vendas/<?= $preorder['id'] ?>">Detalhes</a></td>
                        </tr>
                    <?php endforeach ?>
                    <?php if ($preorders === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Nenhuma pré-venda no histórico.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card mb-4">
        <div class="card-body border-bottom">
            <h2 class="dc-panel-title mb-1">Histórico da garagem</h2>
            <p class="dc-list-meta mb-0">Miniaturas que já foram preparadas, enviadas, recebidas ou canceladas.</p>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Miniatura</th><th>Vendedor</th><th>Valor</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($garageItems as $item): ?>
                        <tr>
                            <td><strong><?= esc($item['product_name']) ?></strong><small class="d-block text-secondary"><?= esc($item['brand']) ?></small></td>
                            <td><?= esc($item['seller_name']) ?></td>
                            <td>R$ <?= number_format((float) $item['total'], 2, ',', '.') ?></td>
                            <td><span class="badge <?= $badgeClass($item['status']) ?>"><?= esc($garageLabels[$item['status']] ?? $item['status']) ?></span></td>
                            <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/garagem/<?= $item['id'] ?>">Detalhes</a></td>
                        </tr>
                    <?php endforeach ?>
                    <?php if ($garageItems === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Nenhum item da garagem no histórico.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="card-body border-bottom">
            <h2 class="dc-panel-title mb-1">Histórico de envios</h2>
            <p class="dc-list-meta mb-0">Envios agrupados, fretes e códigos de rastreio.</p>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Referência</th><th>Vendedor</th><th>Transportadora</th><th>Frete</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($shipments as $shipment): ?>
                        <tr>
                            <td><strong><?= esc($shipment['reference']) ?></strong><small class="d-block text-secondary"><?= esc($shipment['tracking_code'] ?: 'Sem rastreio') ?></small></td>
                            <td><?= esc($shipment['seller_name']) ?></td>
                            <td><?= esc($shipment['carrier']) ?></td>
                            <td>R$ <?= number_format((float) $shipment['shipping_amount'], 2, ',', '.') ?></td>
                            <td><span class="badge <?= $badgeClass($shipment['status']) ?>"><?= esc($shipmentLabels[$shipment['status']] ?? $shipment['status']) ?></span></td>
                            <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/entregas/envios/<?= $shipment['id'] ?>">Ver envio</a></td>
                        </tr>
                    <?php endforeach ?>
                    <?php if ($shipments === []): ?><tr><td colspan="6" class="text-center text-secondary py-4">Nenhum envio organizado.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?= $this->include('partials/footer') ?>
