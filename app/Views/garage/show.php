<?= $this->include('partials/header') ?>

<?php
$balance = max(0, (float) $item['total'] - (float) $item['paid']);
$progress = (float) $item['total'] > 0 ? min(100, ((float) $item['paid'] / (float) $item['total']) * 100) : 0;
$statusClass = match ($item['status']) {
    'received' => 'text-bg-success',
    'shipping' => 'text-bg-primary',
    'canceled' => 'text-bg-danger',
    default => 'text-bg-warning',
};
?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-panel-link" href="/garagem">← Voltar para garagem</a>
            <h1 class="dc-page-title mt-2"><?= esc($item['product_name']) ?></h1>
            <p class="dc-page-subtitle"><?= esc($item['brand']) ?> · vendedor: <?= esc($item['seller_name']) ?></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-primary" href="/garagem/<?= $item['id'] ?>/editar"><i class="bi bi-pencil"></i> Editar</a>
            <?php if ($item['status'] !== 'canceled'): ?>
                <?php if ($balance > 0): ?>
                    <a class="btn btn-primary" href="#registrar-pagamento">
                        <i class="bi bi-plus-lg"></i> Registrar pagamento
                    </a>
                <?php else: ?>
                    <button class="btn btn-outline-success" type="button" disabled title="Este item já está totalmente pago.">
                        <i class="bi bi-check2-circle"></i> Pagamento concluído
                    </button>
                <?php endif ?>
            <?php endif ?>
            <span class="badge <?= $statusClass ?>"><?= esc($labels[$item['status']] ?? $item['status']) ?></span>
        </div>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>
    <?php if (session('errors')): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('esc', session('errors'))) ?></div>
    <?php endif ?>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <section class="card h-100">
                <?php if ($item['image_id']): ?>
                    <img class="dc-product-image" src="/imagens/carrinhos/<?= $item['image_id'] ?>" alt="<?= esc($item['product_name']) ?>">
                <?php else: ?>
                    <div class="dc-empty-image"><i class="bi bi-car-front"></i></div>
                <?php endif ?>
                <div class="card-body">
                    <p class="dc-list-meta mb-1">Comprado em <?= date('d/m/Y', strtotime($item['acquired_at'])) ?></p>
                    <p class="mb-0"><strong><?= (int) $item['quantity'] ?></strong> unidade(s) · R$ <?= number_format((float) $item['unit_price'], 2, ',', '.') ?> cada</p>
                </div>
            </section>
        </div>
        <div class="col-lg-8">
            <div class="row g-3 h-100">
                <div class="col-sm-4">
                    <section class="card dc-stat h-100">
                        <span class="dc-stat-icon primary"><i class="bi bi-tag"></i></span>
                        <p class="dc-stat-label">Valor total</p>
                        <p class="dc-stat-value">R$ <?= number_format((float) $item['total'], 2, ',', '.') ?></p>
                    </section>
                </div>
                <?php if ($balance > 0): ?>
                    <div class="col-sm-4">
                        <section class="card dc-stat h-100">
                            <span class="dc-stat-icon warning"><i class="bi bi-calendar-event"></i></span>
                            <p class="dc-stat-label">Próxima parcela</p>
                            <p class="dc-stat-value fs-5">
                                <?= $item['next_payment_date'] ? esc(date('d/m/Y', strtotime($item['next_payment_date']))) : 'Não informada' ?>
                            </p>
                        </section>
                    </div>
                <?php endif ?>
                <div class="col-sm-4">
                    <section class="card dc-stat h-100">
                        <span class="dc-stat-icon success"><i class="bi bi-check2-circle"></i></span>
                        <p class="dc-stat-label">Total pago</p>
                        <p class="dc-stat-value">R$ <?= number_format((float) $item['paid'], 2, ',', '.') ?></p>
                    </section>
                </div>
                <div class="col-sm-4">
                    <section class="card dc-stat h-100">
                        <span class="dc-stat-icon warning"><i class="bi bi-hourglass-split"></i></span>
                        <p class="dc-stat-label">Saldo</p>
                        <p class="dc-stat-value">R$ <?= number_format($balance, 2, ',', '.') ?></p>
                    </section>
                </div>
                <div class="col-12">
                    <section class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between small mb-2">
                                <strong>Progresso do pagamento</strong>
                                <span><?= number_format($progress, 0) ?>%</span>
                            </div>
                            <div class="progress" role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-success" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <section class="card mb-3">
                <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="dc-panel-title">Pagamentos realizados</h2>
                    <div class="d-flex align-items-center gap-2">
                        <span class="dc-list-meta"><?= count($payments) ?> registro(s)</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Forma de pagamento</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($payment['paid_at'])) ?></td>
                                    <td><?= esc($payment['notes'] ?: 'Parcela') ?></td>
                                    <td><?= esc(ucfirst($payment['method'])) ?></td>
                                    <td class="text-end fw-semibold">R$ <?= number_format((float) $payment['amount'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($payments === []): ?>
                    <p class="text-secondary text-center py-4 mb-0">Nenhum pagamento registrado.</p>
                <?php endif ?>
            </section>

            <?php if ($balance > 0): ?>
                <section class="card" id="registrar-pagamento">
                    <div class="card-body">
                        <h2 class="dc-panel-title mb-3">Registrar pagamento</h2>
                        <form method="post" action="/garagem/<?= $item['id'] ?>/pagamentos">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="amount">Valor</label>
                                    <input class="form-control" id="amount" type="number" min="0.01" max="<?= $balance ?>" step="0.01" name="amount" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="method">Forma</label>
                                    <select class="form-select" id="method" name="method" required>
                                        <option value="pix">Pix</option>
                                        <option value="transferencia">Transferência</option>
                                        <option value="cartao">Cartão</option>
                                        <option value="dinheiro">Dinheiro</option>
                                        <option value="outro">Outro</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="paid_at">Data</label>
                                    <input class="form-control" id="paid_at" type="date" name="paid_at" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="next_payment_date">Próxima parcela</label>
                                    <input class="form-control" id="next_payment_date" type="date" name="next_payment_date"
                                        value="<?= esc($item['next_payment_date'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="payment_notes">Observação</label>
                                    <input class="form-control" id="payment_notes" name="notes">
                                </div>
                            </div>
                            <button class="btn btn-primary mt-3">Registrar pagamento</button>
                        </form>
                    </div>
                </section>
            <?php endif ?>
        </div>

        <div class="col-lg-5">
            <section class="card">
                <div class="card-body">
                    <h2 class="dc-panel-title mb-2">Logística</h2>
                    <p class="text-secondary mb-3">Envio, frete, transportadora e rastreio são organizados em Entregas.</p>
                    <?php if ($balance <= 0 && $item['status'] === 'stored'): ?>
                        <a class="btn btn-primary w-100" href="/entregas/envios/novo?seller_id=<?= (int) $item['seller_id'] ?>">
                            <i class="bi bi-box-seam me-1"></i> Preparar envio
                        </a>
                    <?php elseif ($item['status'] !== 'canceled'): ?>
                        <p class="dc-list-meta mb-0">Quite o item para incluí-lo em uma entrega agrupada.</p>
                    <?php endif ?>
                </div>
            </section>

            <?php if ($item['status'] !== 'canceled'): ?>
                <section class="card border-danger mt-3">
                    <div class="card-body">
                        <h2 class="dc-panel-title mb-2 text-danger">Cancelar item</h2>
                        <p class="text-secondary small mb-3">Use apenas se a compra com o vendedor não for mais concluída.</p>
                        <form method="post" action="/garagem/<?= $item['id'] ?>/status" onsubmit="return confirm('Cancelar este item da garagem?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="canceled">
                            <button class="btn btn-outline-danger w-100">Cancelar item</button>
                        </form>
                    </div>
                </section>
            <?php endif ?>
        </div>
    </div>
</main>

<?= $this->include('partials/footer') ?>
