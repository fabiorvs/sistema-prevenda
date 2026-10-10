<?= $this->include('partials/header') ?>
<main class="dc-content">
    <h1 class="dc-page-title">Financeiro</h1>
    <p class="dc-page-subtitle mb-4">Contas a pagar e pagamentos das suas pré-vendas.</p>
    <?php if(session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Vencimento</th>
                            <th>Descrição</th>
                            <th>Pré-venda</th>
                            <th>Vendedor</th>
                            <th>Status</th>
                            <th class="text-end">Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($entries as $entry): ?><tr>
                            <td><?= $entry['due_date'] ? esc(date('d/m/Y', strtotime($entry['due_date']))) : '—' ?></td>
                            <td><?= esc($entry['description']) ?></td>
                            <td><?= esc($entry['reference']) ?></td>
                            <td><?= esc($entry['seller_name']) ?></td>
                            <td><span
                                    class="badge <?= $entry['status'] === 'paid' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= esc($entry['status']) ?></span>
                            </td>
                            <td class="text-end">R$ <?= number_format((float) $entry['amount'], 2, ',', '.') ?></td>
                            <td class="text-end"><?php if($entry['status'] !== 'paid'): ?><a
                                    class="btn btn-sm btn-primary"
                                    href="/financeiro/contas/<?= $entry['id'] ?>/pagamento">Pagar</a><?php endif ?></td>
                        </tr><?php endforeach ?></tbody>
                </table>
            </div><?php if ($entries === []): ?><p class="text-secondary text-center py-5 mb-0">Nenhuma conta a pagar
                cadastrada ainda.</p><?php endif ?>
        </div>
    </div>
</main><?= $this->include('partials/footer') ?>