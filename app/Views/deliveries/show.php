<?= $this->include('partials/header') ?>

<?php
$today = date('Y-m-d');
$isLate = $order['expected_delivery_date']
    && $order['expected_delivery_date'] < $today
    && ! in_array($order['status'], ['received', 'delivered', 'canceled'], true);
$statusLabel = $labels[$order['status']] ?? 'Aguardando lançamento';
$pendingItems = array_filter(
    $order['items'],
    static fn (array $item): bool => (int) $item['received_quantity'] < (int) $item['quantity'],
);
?>

<main class="dc-content">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <a class="dc-back-button" href="/entregas"><i class="bi bi-arrow-left"></i> Voltar para entregas</a>
            <h1 class="dc-page-title mt-2">Entrega <?= esc($order['reference']) ?></h1>
            <p class="dc-page-subtitle"><?= esc($order['seller_name']) ?> · status: <?= esc($statusLabel) ?></p>
        </div>
        <?php if ($isLate): ?>
            <span class="badge text-bg-danger">Entrega atrasada</span>
        <?php endif ?>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>
    <?php if (session('errors')): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('esc', session('errors'))) ?></div>
    <?php endif ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <?php if ($pendingItems !== []): ?>
                <section class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Chegada ao vendedor</h2>
                        <p class="text-secondary small">
                            Marque aqui as miniaturas que já chegaram ao vendedor e poderão entrar em um envio agrupado.
                        </p>
                    <form method="post" action="/entregas/<?= $order['id'] ?>/recebimento">
                        <?= csrf_field() ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Comprado</th>
                                        <th>No vendedor</th>
                                        <th>Marcar chegada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingItems as $item): ?>
                                        <?php $pending = (int) $item['quantity'] - (int) $item['received_quantity']; ?>
                                        <tr>
                                            <td>
                                                <strong><?= esc($item['product_name']) ?></strong>
                                                <small class="d-block text-secondary"><?= esc($item['brand']) ?></small>
                                            </td>
                                            <td><?= (int) $item['quantity'] ?></td>
                                            <td><?= (int) $item['received_quantity'] ?></td>
                                            <td>
                                                <input
                                                    class="form-control"
                                                    type="number"
                                                    min="0"
                                                    max="<?= $pending ?>"
                                                    value="0"
                                                    name="received[<?= $item['id'] ?>]"
                                                >
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="receipt_actual_date">Data da chegada</label>
                                <input class="form-control" id="receipt_actual_date" type="date" name="actual_delivery_date" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="receipt_tracking_code">Rastreio</label>
                                <input class="form-control" id="receipt_tracking_code" name="tracking_code" value="<?= esc($order['tracking_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="receipt_expected_date">Previsão</label>
                                <input class="form-control" id="receipt_expected_date" type="date" name="expected_delivery_date" value="<?= esc($order['expected_delivery_date'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="receipt_notes">Observações</label>
                                <textarea class="form-control" id="receipt_notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-4">Confirmar chegada ao vendedor</button>
                    </form>
                    </div>
                </section>
            <?php else: ?>
                <section class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-2">Chegada confirmada</h2>
                        <p class="text-secondary mb-3">Todas as miniaturas desta pré-venda já estão disponíveis no vendedor.</p>
                        <a class="btn btn-primary" href="/entregas/envios/novo?seller_id=<?= (int) $order['seller_id'] ?>">
                            <i class="bi bi-box-seam me-1"></i> Organizar envio
                        </a>
                    </div>
                </section>
            <?php endif ?>

        </div>

        <div class="col-lg-5">
            <section class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">Linha do tempo</h2>
                    <?php foreach ($order['events'] as $event): ?>
                        <article class="border-start border-3 border-primary ps-3 pb-3 mb-3">
                            <strong><?= esc($labels[$event['new_status']] ?? $event['new_status']) ?></strong>
                            <small class="d-block text-secondary">
                                <?= esc(date('d/m/Y H:i', strtotime($event['created_at']))) ?>
                            </small>
                            <?php if ($event['product_name']): ?>
                                <p class="mb-1 small">
                                    <?= (int) $event['received_quantity'] ?> unidade(s) de <?= esc($event['product_name']) ?>
                                </p>
                            <?php endif ?>
                            <?php if ($event['tracking_code']): ?>
                                <p class="mb-1 small">Rastreio: <?= esc($event['tracking_code']) ?></p>
                            <?php endif ?>
                            <?php if ($event['notes']): ?>
                                <p class="mb-0 small"><?= esc($event['notes']) ?></p>
                            <?php endif ?>
                        </article>
                    <?php endforeach ?>
                    <?php if ($order['events'] === []): ?>
                        <p class="text-secondary mb-0">Ainda não há movimentações registradas.</p>
                    <?php endif ?>
                </div>
            </section>
        </div>
    </div>
</main>

<?= $this->include('partials/footer') ?>
