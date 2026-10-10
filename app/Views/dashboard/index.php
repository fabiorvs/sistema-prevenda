<?= $this->include('partials/header') ?>

<main class="dc-content">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="dc-page-title">Dashboard</h1>
            <p class="dc-page-subtitle">Resumo das suas pré-vendas e finanças.</p>
        </div>
        <a class="btn btn-outline-primary" href="/dashboard/exportar?<?= esc(http_build_query(array_filter($filters))) ?>">Exportar CSV</a>
    </div>

    <form class="card card-body mb-4" method="get" action="/dashboard">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="seller_id">Vendedor</label>
                <select class="form-select" id="seller_id" name="seller_id">
                    <option value="">Todos os vendedores</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?= $seller['id'] ?>" <?= $filters['seller_id'] === (int) $seller['id'] ? 'selected' : '' ?>><?= esc($seller['name']) ?></option>
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
            <div class="col-md-2"><button class="btn btn-primary w-100">Filtrar</button></div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><section class="card dc-stat"><p class="dc-stat-label">Total de pré-vendas</p><p class="dc-stat-value">R$ <?= number_format($cards['total'], 2, ',', '.') ?></p></section></div>
        <div class="col-sm-6 col-xl-3"><section class="card dc-stat"><p class="dc-stat-label">Total pago</p><p class="dc-stat-value text-success">R$ <?= number_format($cards['paid'], 2, ',', '.') ?></p></section></div>
        <div class="col-sm-6 col-xl-3"><section class="card dc-stat"><p class="dc-stat-label">Saldo a pagar</p><p class="dc-stat-value text-danger">R$ <?= number_format($cards['balance'], 2, ',', '.') ?></p></section></div>
        <div class="col-sm-6 col-xl-3"><section class="card dc-stat"><p class="dc-stat-label">Pedidos pendentes</p><p class="dc-stat-value"><?= $cards['pending'] ?></p></section></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7"><section class="card h-100"><div class="card-body"><h2 class="h5">Compromissos por mês</h2><canvas id="monthlyChart" height="120"></canvas></div></section></div>
        <div class="col-lg-5"><section class="card h-100"><div class="card-body"><h2 class="h5">Resumo por vendedor</h2><?php foreach ($seller_summary as $seller): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?= esc($seller['name']) ?> <small class="text-secondary">(<?= $seller['orders'] ?>)</small></span><strong>R$ <?= number_format($seller['balance'], 2, ',', '.') ?></strong></div><?php endforeach ?><?php if ($seller_summary === []): ?><p class="text-secondary mb-0">Sem dados no período.</p><?php endif ?></div></section></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6"><section class="card"><div class="card-body"><h2 class="h5">Próximas entregas</h2><?php foreach ($upcoming_deliveries as $order): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?= esc($order['reference']) ?> · <?= esc($order['seller_name']) ?></span><strong><?= date('d/m/Y', strtotime($order['expected_delivery_date'])) ?></strong></div><?php endforeach ?><?php if ($upcoming_deliveries === []): ?><p class="text-secondary mb-0">Nenhuma entrega prevista.</p><?php endif ?></div></section></div>
        <div class="col-lg-6"><section class="card"><div class="card-body"><h2 class="h5">Próximos vencimentos</h2><?php foreach ($upcoming_due as $entry): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?= esc($entry['reference']) ?> · <?= esc($entry['seller_name']) ?></span><strong>R$ <?= number_format((float) $entry['amount'], 2, ',', '.') ?></strong></div><?php endforeach ?><?php if ($upcoming_due === []): ?><p class="text-secondary mb-0">Nenhum vencimento pendente.</p><?php endif ?></div></section></div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthly['labels']) ?>,
            datasets: [
                { label: 'A pagar', data: <?= json_encode($monthly['total']) ?>, backgroundColor: '#5646f5' },
                { label: 'Pago', data: <?= json_encode($monthly['paid']) ?>, backgroundColor: '#26b36a' },
            ],
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });
</script>

<?= $this->include('partials/footer') ?>
