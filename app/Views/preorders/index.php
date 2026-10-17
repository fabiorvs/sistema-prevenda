<?= $this->include('partials/header') ?>
<?php
$preorderStatusLabels = [
    'reserved' => 'Reservado',
    'awaiting_release' => 'Aguardando lançamento',
    'awaiting_shipment' => 'Aguardando envio',
    'in_transit' => 'Em transporte',
    'partially_received' => 'Chegada parcial ao vendedor',
    'received' => 'Disponível no vendedor',
    'delivered' => 'Recebido por você',
    'canceled' => 'Cancelado',
    'open' => 'Em aberto',
];
?>
<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <h1 class="dc-page-title">Pré-vendas</h1>
            <p class="dc-page-subtitle">Ordenadas pela próxima previsão de entrega.</p>
        </div><a class="btn btn-primary" href="/pre-vendas/nova">+ Nova pré-venda</a>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>

    <section class="card dc-overall-summary mb-3">
        <div class="card-body">
            <div class="dc-overall-summary-title"><span class="dc-stat-icon primary position-static"><i class="bi bi-receipt"></i></span><div><h2 class="dc-panel-title">Resumo geral de pré-vendas</h2><p class="dc-list-meta mb-0">Todos os itens atualmente em acompanhamento.</p></div></div>
            <div class="dc-overall-summary-metrics"><div><span>Miniaturas</span><strong><?= (int) $overallSummary['items'] ?></strong></div><div><span>Acumulado</span><strong>R$ <?= number_format($overallSummary['total'], 2, ',', '.') ?></strong></div><div><span>Pago</span><strong class="text-success">R$ <?= number_format($overallSummary['paid'], 2, ',', '.') ?></strong></div><div><span>Falta pagar</span><strong class="text-danger">R$ <?= number_format($overallSummary['balance'], 2, ',', '.') ?></strong></div></div>
        </div>
    </section>

    <?php if ($sellerSummary !== []): ?>
        <div class="row g-3 mb-3">
            <?php foreach ($sellerSummary as $summary): ?>
                <?php $balance = max(0, (float) $summary['total'] - (float) $summary['paid']); ?>
                <div class="col-md-6 col-xl-4">
                    <section class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="dc-stat-icon purple position-static flex-shrink-0">
                                    <i class="bi bi-shop"></i>
                                </span>
                                <div>
                                    <h2 class="dc-panel-title"><?= esc($summary['seller_name']) ?></h2>
                                    <p class="dc-list-meta mb-0"><?= (int) $summary['items'] ?> miniatura(s) em pré-venda</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-secondary">Acumulado</span>
                                <strong>R$ <?= number_format((float) $summary['total'], 2, ',', '.') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-secondary">Pago</span>
                                <strong class="text-success">R$ <?= number_format((float) $summary['paid'], 2, ',', '.') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-secondary">Falta pagar</span>
                                <strong class="text-danger">R$ <?= number_format($balance, 2, ',', '.') ?></strong>
                            </div>
                        </div>
                    </section>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <form class="card dc-filter-bar mb-3" method="get" action="/pre-vendas">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="seller_id">Vendedor</label>
                <select class="form-select" id="seller_id" name="seller_id">
                    <option value="">Todos os vendedores</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?= $seller['id'] ?>" <?= $filters['seller_id'] === (int) $seller['id'] ? 'selected' : '' ?>>
                            <?= esc($seller['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-7">
                <p class="dc-list-meta mb-2">Itens organizados em envio, recebidos ou cancelados ficam disponíveis no Histórico.</p>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row g-3">
        <?php foreach ($preorders as $order): ?>
            <div class="col-sm-6 col-xl-4">
            <article class="card h-100 overflow-hidden"><?php if ($order['image_id']): ?><img class="preorder-image"
                    src="/imagens/carrinhos/<?= $order['image_id'] ?>"
                    alt="<?= esc($order['product_name']) ?>"><?php else: ?><div class="preorder-placeholder">🚘</div>
                <?php endif ?><div class="card-body">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="badge text-bg-warning"><?= esc($preorderStatusLabels[$order['status']] ?? $order['status']) ?></span>
                        <small class="text-secondary"><?= esc($order['reference']) ?></small>
                    </div>
                    <h2 class="h5 mt-3 mb-1"><?= esc($order['product_name']) ?></h2>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="preorder-meta"><?= esc($order['brand']) ?></span>
                        <span class="dc-seller-tag"><i class="bi bi-shop"></i><?= esc($order['seller_name']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-3"><span class="preorder-meta">Entrega
                            prevista</span><strong><?= $order['expected_delivery_date'] ? esc(date('d/m/Y', strtotime($order['expected_delivery_date']))) : 'Não informada' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2"><span class="preorder-meta">Total</span><span
                            class="preorder-money">R$ <?= number_format($order['total'], 2, ',', '.') ?></span></div>
                    <div class="d-flex justify-content-between mt-1"><span class="preorder-meta">Sinal</span><span>R$
                            <?= number_format((float)$order['down_payment_amount'], 2, ',', '.') ?></span></div>
                    <div class="d-flex justify-content-between mt-2"><span class="preorder-meta">Falta pagar</span><span
                            class="preorder-balance">R$ <?= number_format($order['balance'], 2, ',', '.') ?></span></div>
                    <div class="d-flex gap-2 mt-3">
                        <a class="btn btn-primary btn-sm flex-grow-1" href="/pre-vendas/<?= $order['id'] ?>">
                            Ver detalhes
                        </a>
                        <a class="btn btn-outline-primary btn-sm" href="/entregas/<?= $order['id'] ?>"
                            aria-label="Gerenciar entrega">
                            <i class="bi bi-box-seam"></i>
                        </a>
                    </div>
                </div>
            </article>
            </div>
        <?php endforeach ?>
    </div>

    <?php if ($preorders === []): ?>
        <div class="text-center text-secondary py-5">Nenhuma pré-venda cadastrada.</div>
    <?php endif ?>

    <div class="dc-pagination mt-4">
        <?= $pager->links('default', 'diecast') ?>
    </div>
</main>

<?= $this->include('partials/footer') ?>
