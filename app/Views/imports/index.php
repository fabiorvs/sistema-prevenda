<?= $this->include('partials/header') ?>
<?php
$statusLabels = [
    'purchased' => 'Compra confirmada',
    'shipped' => 'Em trânsito',
    'customs' => 'Em fiscalização',
    'received' => 'Recebida',
];
?>
<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <h1 class="dc-page-title">Importações</h1>
            <p class="dc-page-subtitle">Acompanhe compras internacionais, custos, rastreios e chegada à coleção.</p>
        </div>
        <a class="btn btn-primary" href="/importacoes/nova"><i class="bi bi-plus-lg"></i> Nova importação</a>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>

    <div class="row g-3">
        <?php foreach ($imports as $import): ?>
            <?php $cost = ((float) $import['item_amount'] + (float) $import['shipping_amount'] + (float) $import['tax_amount']) * (float) $import['exchange_rate']; ?>
            <div class="col-md-6 col-xl-4">
                <section class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <span class="badge <?= $import['status'] === 'received' ? 'text-bg-success' : 'text-bg-primary' ?>"><?= esc($statusLabels[$import['status']] ?? $import['status']) ?></span>
                            <small class="text-secondary"><?= esc($import['currency']) ?></small>
                        </div>
                        <h2 class="h6 mt-3 mb-1"><?= esc($import['brand'] . ' — ' . $import['name']) ?></h2>
                        <span class="dc-seller-tag"><i class="bi bi-shop"></i><?= esc($import['supplier_name']) ?></span>
                        <dl class="row small mt-3 mb-0">
                            <dt class="col-6 text-secondary">Rastreio</dt><dd class="col-6 text-end"><?= esc($import['tracking_code'] ?: 'Não informado') ?></dd>
                            <dt class="col-6 text-secondary">Previsão</dt><dd class="col-6 text-end"><?= $import['expected_at'] ? date('d/m/Y', strtotime($import['expected_at'])) : 'Não informada' ?></dd>
                            <dt class="col-6 text-secondary">Custo estimado</dt><dd class="col-6 text-end fw-semibold">R$ <?= number_format($cost, 2, ',', '.') ?></dd>
                        </dl>
                        <?php if ($import['status'] !== 'received'): ?>
                            <form class="mt-3" method="post" action="/importacoes/<?= $import['id'] ?>/receber">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-check2-circle"></i> Marcar como recebida</button>
                            </form>
                        <?php elseif (! empty($import['collection_id'])): ?>
                            <a class="btn btn-outline-primary btn-sm w-100 mt-3" href="/colecao/<?= $import['collection_id'] ?>"><i class="bi bi-grid-3x3-gap"></i> Ver na coleção</a>
                        <?php endif ?>
                    </div>
                </section>
            </div>
        <?php endforeach ?>
        <?php if ($imports === []): ?>
            <div class="col-12"><div class="card card-body text-center text-secondary">Nenhuma importação cadastrada.</div></div>
        <?php endif ?>
    </div>
</main>
<?= $this->include('partials/footer') ?>
