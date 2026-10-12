<?= $this->include('partials/header') ?>

<main class="dc-content">
    <div class="dc-page-heading dashboard-hero">
        <div>
            <span class="dashboard-eyebrow"><i class="bi bi-stars"></i> Visão geral da coleção</span>
            <h1 class="dc-page-title">Olá, <?= esc(auth()->user()->username ?? 'Colecionador') ?>!</h1>
            <p class="dc-page-subtitle">Acompanhe suas pré-vendas, garagem, entregas e investimento.</p>
        </div>
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="/pre-vendas/nova"><i class="bi bi-plus-lg me-1"></i> Nova pré-venda</a><a class="btn btn-outline-primary" href="/dashboard/exportar?<?= esc(http_build_query(array_filter($filters))) ?>"><i class="bi bi-download me-1"></i> Exportar CSV</a></div>
    </div>

    <form class="card dc-filter-bar mb-3" method="get" action="/dashboard">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
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
            <div class="col-md-3">
                <label class="form-label" for="from">De</label>
                <input class="form-control" id="from" type="date" name="from" value="<?= esc($filters['from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="to">Até</label>
                <input class="form-control" id="to" type="date" name="to" value="<?= esc($filters['to'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <span class="dc-stat-icon primary"><i class="bi bi-cart3"></i></span>
                <p class="dc-stat-label">Comprometido em pré-vendas</p>
                <p class="dc-stat-value">R$ <?= number_format($cards['total'], 2, ',', '.') ?></p>
                <p class="dc-stat-note"><?= count($orders) ?> pedido(s)</p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <span class="dc-stat-icon success"><i class="bi bi-wallet2"></i></span>
                <p class="dc-stat-label">Total pago</p>
                <p class="dc-stat-value">R$ <?= number_format($cards['paid'], 2, ',', '.') ?></p>
                <p class="dc-stat-note">Entradas e parcelas confirmadas</p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <span class="dc-stat-icon warning"><i class="bi bi-coin"></i></span>
                <p class="dc-stat-label">Total a pagar</p>
                <p class="dc-stat-value">R$ <?= number_format($cards['balance'], 2, ',', '.') ?></p>
                <p class="dc-stat-note">Saldo futuro</p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <span class="dc-stat-icon purple"><i class="bi bi-truck"></i></span>
                <p class="dc-stat-label">Pré-vendas com saldo</p>
                <p class="dc-stat-value"><?= (int) $cards['pending'] ?></p>
                <p class="dc-stat-note">Com saldo em aberto</p>
            </section>
        </div>
    </div>

    <?php if ($alerts !== []): ?>
        <section class="card border-warning mb-3">
            <div class="card-body">
                <h2 class="dc-panel-title mb-3"><i class="bi bi-bell me-1"></i> Requer atenção</h2>
                <div class="row g-2">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="col-md-4">
                            <a class="text-decoration-none" href="<?= esc($alert['url']) ?>">
                                <div class="border rounded-3 p-3 h-100 <?= $alert['type'] === 'danger' ? 'border-danger' : 'border-warning' ?>">
                                    <strong class="d-block <?= $alert['type'] === 'danger' ? 'text-danger' : 'text-warning-emphasis' ?>"><?= esc($alert['label']) ?></strong>
                                    <span class="small text-secondary"><?= esc($alert['description']) ?></span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </section>
    <?php endif ?>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <section class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="dc-panel-title">Compromissos por mês</h2>
                        <span class="dc-list-meta"><i class="bi bi-square-fill text-primary me-1"></i>Inclui pré-vendas e garagem</span>
                    </div>
                    <canvas id="monthlyChart" height="104"></canvas>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="dc-panel-title">Status das pré-vendas</h2>
                        <span class="dc-list-meta"><?= count($orders) ?> pedido(s)</span>
                    </div>
                    <div class="dashboard-status-chart"><canvas id="statusChart"></canvas><strong><?= count($orders) ?><small>pedidos</small></strong></div>
                    <div class="dashboard-status-list"><?php foreach ($status_summary['labels'] as $index => $label): ?><?php if ($status_summary['values'][$index] > 0): ?><div><span><i style="background: <?= $status_summary['colors'][$index] ?>"></i><?= esc($label) ?></span><strong><?= (int) $status_summary['values'][$index] ?></strong></div><?php endif ?><?php endforeach ?></div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <section class="card h-100 dashboard-collection-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center"><span class="dc-stat-icon purple position-static"><i class="bi bi-grid-3x3-gap"></i></span><a class="dc-panel-link" href="/colecao">Abrir coleção</a></div><p class="dc-stat-label mt-3">Sua coleção</p><p class="dc-stat-value"><?= number_format($collection_summary['items'], 0, ',', '.') ?> miniatura(s)</p><div class="dashboard-collection-value"><span>Investido</span><strong>R$ <?= number_format($collection_summary['invested'], 2, ',', '.') ?></strong></div><div class="dashboard-collection-value"><span>Importações em acompanhamento</span><strong><?= (int) $collection_summary['imports'] ?></strong></div></div></section>
        </div>
        <div class="col-lg-4">
            <section class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="dc-panel-title">Próximas entregas</h2>
                        <a class="dc-panel-link" href="/entregas">Ver todas</a>
                    </div>
                    <?php foreach ($upcoming_deliveries as $order): ?>
                        <div class="dc-list-row">
                            <div class="d-flex align-items-center gap-3 dc-list-main">
                                <span class="dc-stat-icon primary position-static flex-shrink-0">
                                    <i class="bi bi-car-front"></i>
                                </span>
                                <div class="dc-list-main">
                                    <p class="dc-list-title" title="<?= esc($order['product_names'] ?: $order['reference'], 'attr') ?>"><?= esc($order['product_names'] ?: $order['reference']) ?></p>
                                    <p class="dc-list-meta"><?= esc($order['seller_name']) ?> · <?= esc($order['reference']) ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <strong class="small"><?= date('d/m/Y', strtotime($order['expected_delivery_date'])) ?></strong>
                                <span class="badge text-bg-primary d-block mt-1">Prevista</span>
                            </div>
                        </div>
                    <?php endforeach ?>
                    <?php if ($upcoming_deliveries === []): ?>
                        <p class="text-secondary small mb-0 py-4 text-center">Nenhuma entrega prevista.</p>
                    <?php endif ?>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="dc-panel-title">Pagamentos vencendo</h2>
                        <a class="dc-panel-link" href="/financeiro">Ver todos</a>
                    </div>
                    <?php foreach ($upcoming_due as $entry): ?>
                        <div class="dc-list-row">
                            <div class="d-flex align-items-center gap-3 dc-list-main">
                                <span class="dc-stat-icon warning position-static flex-shrink-0">
                                    <i class="bi bi-receipt-cutoff"></i>
                                </span>
                                <div class="dc-list-main">
                                    <p class="dc-list-title" title="<?= esc($entry['product_names'] ?: $entry['reference'], 'attr') ?>"><?= esc($entry['product_names'] ?: $entry['reference']) ?></p>
                                    <p class="dc-list-meta"><?= esc($entry['seller_name']) ?> · <?= esc($entry['reference']) ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <strong class="small">R$ <?= number_format((float) $entry['amount'], 2, ',', '.') ?></strong>
                                <span class="dc-list-meta d-block">
                                    <?= $entry['due_date'] ? date('d/m/Y', strtotime($entry['due_date'])) : 'Sem vencimento' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach ?>
                    <?php if ($upcoming_due === []): ?>
                        <p class="text-secondary small mb-0 py-4 text-center">Nenhum vencimento pendente.</p>
                    <?php endif ?>
                </div>
            </section>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthly['labels']) ?>,
            datasets: [
                {
                    label: 'A pagar',
                    data: <?= json_encode($monthly['total']) ?>,
                    backgroundColor: '#438df4',
                    borderRadius: 4,
                    barThickness: 14,
                },
                {
                    label: 'Pago',
                    data: <?= json_encode($monthly['paid']) ?>,
                    backgroundColor: '#35c47a',
                    borderRadius: 4,
                    barThickness: 14,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                x: { grid: { display: false }, ticks: { color: '#7a8498', font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: '#edf0f5' }, ticks: { color: '#7a8498', font: { size: 10 } } },
            },
            plugins: {
                legend: { display: false },
            },
        },
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($status_summary['labels']) ?>, datasets: [{ data: <?= json_encode($status_summary['values']) ?>, backgroundColor: <?= json_encode($status_summary['colors']) ?>, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false }, tooltip: { enabled: true } } },
    });
</script>

<?= $this->include('partials/footer') ?>
