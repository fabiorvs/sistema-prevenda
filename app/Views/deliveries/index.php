<?= $this->include('partials/header') ?>

<?php
$today = date('Y-m-d');
?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <h1 class="dc-page-title">Entregas</h1>
            <p class="dc-page-subtitle">Registre chegadas ao vendedor e organize envios agrupados.</p>
        </div>
        <a class="btn btn-primary" href="/entregas/envios/novo">
            <i class="bi bi-box-seam"></i> Organizar envio
        </a>
    </div>

    <form class="card card-body mb-4" method="get" action="/entregas">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="status">Status logístico</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos os status</option>
                    <?php foreach ($labels as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="late" name="late" value="1" <?= $filters['late'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="late">Mostrar somente entregas atrasadas</label>
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h2 class="dc-panel-title">Envios organizados</h2>
            <p class="dc-page-subtitle">Pacotes agrupados por vendedor.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($shipments as $shipment): ?>
            <?php
            $shipmentIsLate = $shipment['expected_delivery_date']
                && $shipment['expected_delivery_date'] < $today
                && ! in_array($shipment['status'], ['received', 'canceled'], true);
            $shipmentClass = match ($shipment['status']) {
                'received' => 'text-bg-success',
                'canceled' => 'text-bg-danger',
                'shipped' => 'text-bg-primary',
                default => 'text-bg-warning',
            };
            ?>
            <div class="col-md-6 col-xl-4">
                <article class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between gap-2 mb-3">
                            <span class="badge <?= $shipmentClass ?>">
                                <?= esc($shipmentLabels[$shipment['status']] ?? $shipment['status']) ?>
                            </span>
                            <small class="text-secondary"><?= esc($shipment['reference']) ?></small>
                        </div>
                        <h3 class="h6 mb-1"><?= esc($shipment['seller_name']) ?></h3>
                        <p class="text-secondary small mb-3">
                            <?= (int) $shipment['total_quantity'] ?> miniatura(s) · <?= esc($shipment['carrier']) ?>
                        </p>
                        <div class="border-top pt-3 mt-auto">
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-secondary">Frete</span>
                                <strong>R$ <?= number_format((float) $shipment['shipping_amount'], 2, ',', '.') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small mb-3">
                                <span class="text-secondary">Rastreio</span>
                                <strong><?= esc($shipment['tracking_code'] ?: 'Não informado') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small mb-3 <?= $shipmentIsLate ? 'text-danger fw-semibold' : '' ?>">
                                <span>Previsão</span>
                                <strong><?= $shipment['expected_delivery_date'] ? esc(date('d/m/Y', strtotime($shipment['expected_delivery_date']))) : 'Não informada' ?></strong>
                            </div>
                            <?php if ($shipmentIsLate): ?>
                                <p class="text-danger small fw-semibold mb-3">Entrega atrasada</p>
                            <?php endif ?>
                            <a class="btn btn-outline-primary w-100" href="/entregas/envios/<?= $shipment['id'] ?>">
                                Ver envio
                            </a>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach ?>
        <?php if ($shipments === []): ?>
            <div class="col-12">
                <div class="card card-body text-center text-secondary">Nenhum envio agrupado organizado.</div>
            </div>
        <?php endif ?>
    </div>

    <div class="mb-3">
        <h2 class="dc-panel-title">Chegadas por pré-venda</h2>
        <p class="dc-page-subtitle">Marque os itens que já estão disponíveis no vendedor.</p>
    </div>

    <div class="row g-3">
        <?php foreach ($orders as $order): ?>
            <?php
            $isLate = $order['expected_delivery_date']
                && $order['expected_delivery_date'] < $today
                && ! in_array($order['status'], ['received', 'delivered', 'canceled'], true);
            $statusLabel = $labels[$order['status']] ?? 'Aguardando lançamento';
            ?>
            <div class="col-md-6 col-xl-4">
                <article class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between gap-2 mb-3">
                            <span class="badge <?= $isLate ? 'text-bg-danger' : 'text-bg-primary' ?>">
                                <?= esc($statusLabel) ?>
                            </span>
                            <small class="text-secondary"><?= esc($order['reference']) ?></small>
                        </div>
                        <h2 class="h5"><?= esc($order['seller_name']) ?></h2>
                        <p class="text-secondary small">
                            <?= (int) $order['received_quantity'] ?> de <?= (int) $order['total_quantity'] ?> item(ns) no vendedor
                        </p>
                        <div class="border-top pt-3 mt-auto">
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-secondary">Previsão</span>
                                <strong>
                                    <?= $order['expected_delivery_date'] ? esc(date('d/m/Y', strtotime($order['expected_delivery_date']))) : 'Não informada' ?>
                                </strong>
                            </div>
                            <?php if ($isLate): ?>
                                <p class="text-danger small fw-semibold mb-2">Entrega atrasada</p>
                            <?php endif ?>
                            <a class="btn btn-outline-primary w-100" href="/entregas/<?= $order['id'] ?>">Registrar chegada</a>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach ?>
    </div>

    <?php if ($orders === []): ?>
        <div class="text-center text-secondary py-5">Nenhuma entrega encontrada para os filtros informados.</div>
    <?php endif ?>
</main>

<?= $this->include('partials/footer') ?>
