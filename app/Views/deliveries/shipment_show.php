<?= $this->include('partials/header') ?>
<?php
$statusClasses = [
    'organized' => 'text-bg-warning',
    'shipped' => 'text-bg-primary',
    'received' => 'text-bg-success',
    'canceled' => 'text-bg-danger',
];
$totalQuantity = array_sum(array_map(static fn (array $item): int => (int) $item['quantity'], $shipment['items']));
$isLate = $shipment['expected_delivery_date']
    && $shipment['expected_delivery_date'] < date('Y-m-d')
    && ! in_array($shipment['status'], ['received', 'canceled'], true);
?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-back-button" href="/entregas">
                <i class="bi bi-arrow-left"></i> Voltar para entregas
            </a>
            <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                <h1 class="dc-page-title">Envio <?= esc($shipment['reference']) ?></h1>
                <span class="badge <?= esc($statusClasses[$shipment['status']] ?? 'text-bg-secondary') ?>">
                    <?= esc($labels[$shipment['status']] ?? $shipment['status']) ?>
                </span>
                <?php if ($isLate): ?>
                    <span class="badge text-bg-danger">Entrega atrasada</span>
                <?php endif ?>
            </div>
            <p class="dc-page-subtitle">
                <?= esc($shipment['seller_name']) ?> · <?= $totalQuantity ?> miniatura(s)
            </p>
        </div>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>
    <?php if (session('errors')): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('esc', session('errors'))) ?></div>
    <?php endif ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="card">
                <div class="card-body">
                    <h2 class="dc-detail-card-title">Miniaturas do envio</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Miniatura</th>
                                    <th>Origem</th>
                                    <th>Quantidade</th>
                                    <th class="text-end">Frete rateado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shipment['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($item['image_id']): ?>
                                                    <img class="dc-table-thumb"
                                                        src="/imagens/carrinhos/<?= $item['image_id'] ?>"
                                                        alt="<?= esc($item['product_name']) ?>">
                                                <?php else: ?>
                                                    <span class="dc-table-thumb dc-table-thumb-empty">
                                                        <i class="bi bi-car-front"></i>
                                                    </span>
                                                <?php endif ?>
                                                <div>
                                                    <strong><?= esc($item['product_name']) ?></strong>
                                                    <small class="d-block text-secondary">
                                                        <?= esc($item['brand']) ?><?= $item['scale'] ? ' · ' . esc($item['scale']) : '' ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="dc-panel-link" href="<?= esc($item['source_url'], 'attr') ?>">
                                                <?= esc($item['source_label']) ?> · <?= esc($item['source_reference']) ?>
                                            </a>
                                        </td>
                                        <td><?= (int) $item['quantity'] ?></td>
                                        <td class="text-end">R$ <?= number_format((float) $item['freight_amount'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="card card-body mb-4">
                <h2 class="dc-detail-card-title">Resumo do envio</h2>
                <dl class="dc-delivery-list">
                    <div>
                        <dt>Vendedor</dt>
                        <dd><?= esc($shipment['seller_name']) ?></dd>
                    </div>
                    <div>
                        <dt>Transportadora</dt>
                        <dd><?= esc($shipment['carrier']) ?></dd>
                    </div>
                    <div>
                        <dt>Frete</dt>
                        <dd>R$ <?= number_format((float) $shipment['shipping_amount'], 2, ',', '.') ?></dd>
                    </div>
                    <div>
                        <dt>Rastreio</dt>
                        <dd><?= esc($shipment['tracking_code'] ?: 'Não informado') ?></dd>
                    </div>
                    <div>
                        <dt>Data de envio</dt>
                        <dd><?= $shipment['shipped_at'] ? esc(date('d/m/Y', strtotime($shipment['shipped_at']))) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Previsão de entrega</dt>
                        <dd class="<?= $isLate ? 'text-danger fw-semibold' : '' ?>">
                            <?= $shipment['expected_delivery_date'] ? esc(date('d/m/Y', strtotime($shipment['expected_delivery_date']))) : 'Não informada' ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Recebido em</dt>
                        <dd><?= $shipment['received_at'] ? esc(date('d/m/Y', strtotime($shipment['received_at']))) : '—' ?></dd>
                    </div>
                </dl>
                <?php if ($shipment['notes']): ?>
                    <p class="small text-secondary border-top pt-3 mb-0"><?= nl2br(esc($shipment['notes'])) ?></p>
                <?php endif ?>
            </section>

            <section class="card card-body">
                <h2 class="dc-detail-card-title">Atualizar envio</h2>
                <form method="post" action="/entregas/envios/<?= $shipment['id'] ?>/status">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="shipment-status">Status</label>
                        <select class="form-select" id="shipment-status" name="status" required>
                            <?php foreach ($labels as $value => $label): ?>
                                <option value="<?= esc($value) ?>" <?= $shipment['status'] === $value ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="shipment-tracking">Código de rastreio</label>
                        <input class="form-control"
                            id="shipment-tracking"
                            name="tracking_code"
                            value="<?= esc($shipment['tracking_code'] ?? '') ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="shipment-date">Data do envio</label>
                            <input class="form-control"
                                id="shipment-date"
                                type="date"
                                name="shipped_at"
                                value="<?= esc($shipment['shipped_at'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="shipment-expected">Previsão de entrega</label>
                            <input class="form-control"
                                id="shipment-expected"
                                type="date"
                                name="expected_delivery_date"
                                value="<?= esc($shipment['expected_delivery_date'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="shipment-received">Recebimento</label>
                            <input class="form-control"
                                id="shipment-received"
                                type="date"
                                name="received_at"
                                value="<?= esc($shipment['received_at'] ?? '') ?>">
                        </div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Salvar situação</button>
                </form>
            </section>
        </div>
    </div>
</main>

<?= $this->include('partials/footer') ?>
