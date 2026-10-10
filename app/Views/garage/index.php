<?= $this->include('partials/header') ?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <h1 class="dc-page-title">Garagem</h1>
            <p class="dc-page-subtitle">Miniaturas em compra, disponíveis e guardadas com seus vendedores.</p>
        </div>
        <a class="btn btn-primary" href="/garagem/nova">
            <i class="bi bi-plus-lg me-1"></i> Novo item
        </a>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>

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
                                <p class="dc-list-meta mb-0"><?= (int) $summary['items'] ?> miniatura(s) guardada(s)</p>
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

    <form class="card dc-filter-bar mb-3" method="get" action="/garagem">
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
                <p class="dc-list-meta mb-2">Itens em envio, recebidos ou cancelados ficam disponíveis no Histórico.</p>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <?php
            $balance = max(0, (float) $item['total'] - (float) $item['paid']);
            $badge = match ($item['status']) {
                'received' => 'text-bg-success',
                'shipping' => 'text-bg-primary',
                'canceled' => 'text-bg-danger',
                default => 'text-bg-warning',
            };
            ?>
            <div class="col-sm-6 col-xl-3">
                <article class="card h-100">
                    <?php if ($item['image_id']): ?>
                        <img class="dc-product-image" src="/imagens/carrinhos/<?= $item['image_id'] ?>" alt="<?= esc($item['product_name']) ?>">
                    <?php else: ?>
                        <div class="dc-empty-image"><i class="bi bi-car-front"></i></div>
                    <?php endif ?>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <span class="badge <?= $badge ?>"><?= esc($labels[$item['status']] ?? $item['status']) ?></span>
                            <small class="text-secondary"><?= (int) $item['quantity'] ?> un.</small>
                        </div>
                        <h2 class="h6 mb-1"><?= esc($item['product_name']) ?></h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span class="dc-list-meta"><?= esc($item['brand']) ?></span>
                            <span class="dc-seller-tag"><i class="bi bi-shop"></i><?= esc($item['seller_name']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-secondary">Total</span>
                            <strong>R$ <?= number_format((float) $item['total'], 2, ',', '.') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between small mb-3">
                            <span class="text-secondary">Saldo</span>
                            <strong class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                                R$ <?= number_format($balance, 2, ',', '.') ?>
                            </strong>
                        </div>
                        <a class="btn btn-outline-primary btn-sm w-100 mt-auto" href="/garagem/<?= $item['id'] ?>">Abrir controle</a>
                    </div>
                </article>
            </div>
        <?php endforeach ?>
    </div>

    <?php if ($items === []): ?>
        <div class="card">
            <p class="text-secondary text-center py-5 mb-0">Nenhum item ativo na garagem. Consulte o Histórico para itens enviados, recebidos ou cancelados.</p>
        </div>
    <?php endif ?>

    <div class="dc-pagination mt-4"><?= $pager->links('default', 'diecast') ?></div>
</main>

<?= $this->include('partials/footer') ?>
