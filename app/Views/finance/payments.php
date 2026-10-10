<?= $this->include('partials/header') ?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-back-button" href="/financeiro"><i class="bi bi-arrow-left"></i> Voltar para financeiro</a>
            <h1 class="dc-page-title mt-2">Pagamentos</h1>
            <p class="dc-page-subtitle">Histórico de pagamentos e estornos das pré-vendas.</p>
        </div>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>
    <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif ?>

    <section class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pré-venda</th>
                        <th>Tipo</th>
                        <th>Forma</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= esc(date('d/m/Y H:i', strtotime($payment['paid_at']))) ?></td>
                            <td><span class="text-primary fw-semibold"><?= esc($payment['reference']) ?></span></td>
                            <td>
                                <span class="badge <?= $payment['type'] === 'refund' ? 'text-bg-danger' : 'text-bg-success' ?>">
                                    <?= $payment['type'] === 'refund' ? 'Estorno' : 'Pagamento' ?>
                                </span>
                            </td>
                            <td><?= esc(ucfirst($payment['method'] ?? '—')) ?></td>
                            <td class="text-end fw-semibold">R$ <?= number_format((float) $payment['amount'], 2, ',', '.') ?></td>
                            <td class="text-end">
                                <?php if ($payment['type'] === 'payment' && $payment['status'] === 'confirmed'): ?>
                                    <form method="post" action="/financeiro/pagamentos/<?= $payment['id'] ?>/estorno" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Estornar este pagamento?')">Estornar</button>
                                    </form>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php if ($payments === []): ?>
            <p class="text-secondary text-center py-5 mb-0">Nenhum pagamento registrado.</p>
        <?php endif ?>
    </section>
</main>

<?= $this->include('partials/footer') ?>
