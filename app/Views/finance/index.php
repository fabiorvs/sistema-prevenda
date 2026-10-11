<?= $this->include('partials/header') ?>

<?php
$statusLabels = [
    'paid' => ['Pago', 'text-bg-success'],
    'partial' => ['Parcial', 'text-bg-primary'],
    'overdue' => ['Vencido', 'text-bg-danger'],
    'pending' => ['Pendente', 'text-bg-warning'],
];
$paginationParams = array_filter($filters, static fn (string $value): bool => $value !== '');
$pageUrl = static function (int $page) use ($paginationParams): string {
    return '/financeiro?' . http_build_query($paginationParams + ['page' => $page]);
};
$firstPage = max(1, $pagination['page'] - 2);
$lastPage = min($pagination['pages'], $pagination['page'] + 2);
?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <h1 class="dc-page-title">Financeiro</h1>
            <p class="dc-page-subtitle">Acompanhe contas de pré-vendas, Garagem, pagamentos e o saldo a investir.</p>
        </div>
        <a class="btn btn-outline-primary" href="/financeiro/pagamentos">
            <i class="bi bi-clock-history me-1"></i> Histórico de pagamentos
        </a>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>

    <div class="row g-3 mb-3" id="resumo">
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <span class="dc-stat-icon primary"><i class="bi bi-receipt"></i></span>
                <p class="dc-stat-label">A pagar agora</p>
                <p class="dc-stat-value">R$ <?= number_format($overview['due_now'], 2, ',', '.') ?></p>
                <p class="dc-stat-note">Vencido ou com vencimento hoje</p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <span class="dc-stat-icon warning"><i class="bi bi-calendar-event"></i></span>
                <p class="dc-stat-label">Próximos 30 dias</p>
                <p class="dc-stat-value">R$ <?= number_format($overview['next_30_days'], 2, ',', '.') ?></p>
                <p class="dc-stat-note">Parcelas programadas</p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <span class="dc-stat-icon success"><i class="bi bi-graph-up-arrow"></i></span>
                <p class="dc-stat-label">Investido no mês</p>
                <p class="dc-stat-value">R$ <?= number_format($overview['invested_month'], 2, ',', '.') ?></p>
                <p class="dc-stat-note">Pagamentos e fretes enviados</p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <span class="dc-stat-icon purple"><i class="bi bi-wallet2"></i></span>
                <p class="dc-stat-label">Saldo total futuro</p>
                <p class="dc-stat-value">R$ <?= number_format($summary['balance'], 2, ',', '.') ?></p>
                <p class="dc-stat-note">Pré-vendas e garagem</p>
            </section>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-label="Filtros rápidos de contas">
        <span class="small fw-semibold text-secondary me-1">Mostrar:</span>
        <a class="btn btn-sm <?= $filters['status'] === '' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="/financeiro">
            Todas
        </a>
        <a class="btn btn-sm <?= $filters['status'] === 'pending' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="/financeiro?status=pending">
            Pendentes
        </a>
        <a class="btn btn-sm <?= $filters['status'] === 'partial' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="/financeiro?status=partial">
            Parciais
        </a>
        <a class="btn btn-sm <?= $filters['status'] === 'overdue' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="/financeiro?status=overdue">
            Vencidas
        </a>
        <a class="btn btn-sm <?= $filters['status'] === 'paid' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="/financeiro?status=paid">
            Pagas
        </a>
    </div>

    <form class="card dc-filter-bar mb-3" method="get" action="/financeiro">
        <div class="row g-2">
            <div class="col-md-8">
                <label class="visually-hidden" for="q">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                    <input class="form-control border-start-0" id="q" name="q" value="<?= esc($filters['q']) ?>" placeholder="Pesquisar descrição, origem ou vendedor...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="visually-hidden" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos os status</option>
                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pendente</option>
                    <option value="partial" <?= $filters['status'] === 'partial' ? 'selected' : '' ?>>Parcial</option>
                    <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Pago</option>
                    <option value="overdue" <?= $filters['status'] === 'overdue' ? 'selected' : '' ?>>Vencido</option>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100" type="submit">Filtrar</button>
            </div>
        </div>
    </form>

    <section class="card">
        <div class="card-body border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
            <div>
                <h2 class="dc-panel-title mb-1">Contas a pagar</h2>
                <p class="dc-list-meta mb-0"><?= count($entries) ?> lançamento(s) encontrado(s)</p>
            </div>
            <?php if ($filters['q'] !== '' || $filters['status'] !== ''): ?>
                <a class="btn btn-sm btn-outline-secondary" href="/financeiro">
                    <i class="bi bi-x-lg me-1"></i> Limpar filtros
                </a>
            <?php endif ?>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Vencimento / compra</th>
                        <th>Descrição</th>
                        <th>Origem</th>
                        <th>Vendedor</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Pago</th>
                        <th class="text-end">Saldo</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <?php [$statusLabel, $statusClass] = $statusLabels[$entry['display_status']]; ?>
                        <tr>
                            <td class="<?= $entry['display_status'] === 'overdue' ? 'text-danger fw-semibold' : '' ?>">
                                <?= $entry['display_date'] ? esc(date('d/m/Y', strtotime($entry['display_date']))) : '—' ?>
                            </td>
                            <td><?= esc($entry['description']) ?></td>
                            <td>
                                <a class="text-primary fw-semibold text-decoration-none" href="<?= esc($entry['source_url']) ?>">
                                    <?= esc($entry['source_label']) ?>
                                </a>
                            </td>
                            <td><?= esc($entry['seller_name']) ?></td>
                            <td class="text-end">R$ <?= number_format((float) $entry['amount'], 2, ',', '.') ?></td>
                            <td class="text-end text-success">R$ <?= number_format((float) $entry['paid_amount'], 2, ',', '.') ?></td>
                            <td class="text-end fw-semibold">R$ <?= number_format((float) $entry['balance'], 2, ',', '.') ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td class="text-end">
                                <?php if ($entry['display_status'] !== 'paid'): ?>
                                    <a class="btn btn-sm btn-primary" href="<?= esc($entry['payment_url']) ?>">Pagar</a>
                                <?php else: ?>
                                    <i class="bi bi-check-circle text-success" aria-label="Quitado"></i>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php if ($entries === []): ?>
            <p class="text-secondary text-center py-5 mb-0">Nenhuma conta encontrada.</p>
        <?php endif ?>
        <?php if ($pagination['total'] > 0): ?>
            <div class="card-body border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
                <p class="dc-list-meta mb-0">Exibindo <?= (($pagination['page'] - 1) * $pagination['per_page']) + 1 ?>–<?= min($pagination['page'] * $pagination['per_page'], $pagination['total']) ?> de <?= $pagination['total'] ?> lançamento(s)</p>
                <?php if ($pagination['pages'] > 1): ?>
                    <nav class="dc-pagination" aria-label="Paginação de contas a pagar">
                        <ul class="pagination mb-0">
                            <li class="page-item <?= $pagination['page'] === 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $pagination['page'] > 1 ? $pageUrl($pagination['page'] - 1) : '#' ?>" aria-label="Página anterior"><i class="bi bi-chevron-left"></i></a></li>
                            <?php if ($firstPage > 1): ?><li class="page-item"><a class="page-link" href="<?= $pageUrl(1) ?>">1</a></li><?php if ($firstPage > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?><?php endif ?>
                            <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++): ?><li class="page-item <?= $pageNumber === $pagination['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= $pageUrl($pageNumber) ?>"><?= $pageNumber ?></a></li><?php endfor ?>
                            <?php if ($lastPage < $pagination['pages']): ?><?php if ($lastPage < $pagination['pages'] - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?><li class="page-item"><a class="page-link" href="<?= $pageUrl($pagination['pages']) ?>"><?= $pagination['pages'] ?></a></li><?php endif ?>
                            <li class="page-item <?= $pagination['page'] === $pagination['pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= $pagination['page'] < $pagination['pages'] ? $pageUrl($pagination['page'] + 1) : '#' ?>" aria-label="Próxima página"><i class="bi bi-chevron-right"></i></a></li>
                        </ul>
                    </nav>
                <?php endif ?>
            </div>
        <?php endif ?>
    </section>
</main>

<?= $this->include('partials/footer') ?>
