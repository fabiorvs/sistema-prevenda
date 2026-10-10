<?= $this->include('partials/header') ?>

<?php
$today = date('Y-m-d');
?>

<main class="dc-content">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="dc-page-title">Entregas</h1>
            <p class="dc-page-subtitle">Acompanhe previsão, rastreio e recebimentos dos seus carrinhos.</p>
        </div>
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

    <div class="row g-3">
        <?php foreach ($orders as $order): ?>
            <?php
            $isLate = $order['expected_delivery_date']
                && $order['expected_delivery_date'] < $today
                && ! in_array($order['status'], ['received', 'canceled'], true);
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
                            <?= (int) $order['received_quantity'] ?> de <?= (int) $order['total_quantity'] ?> item(ns) recebidos
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
                            <a class="btn btn-outline-primary w-100" href="/entregas/<?= $order['id'] ?>">Gerenciar entrega</a>
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
