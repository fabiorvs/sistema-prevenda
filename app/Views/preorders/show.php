<?= $this->include('partials/header') ?>
<?php
$statusClasses = [
    'reserved' => 'text-bg-info',
    'awaiting_release' => 'text-bg-warning',
    'awaiting_shipment' => 'text-bg-primary',
    'in_transit' => 'text-bg-primary',
    'partially_received' => 'text-bg-warning',
    'received' => 'text-bg-success',
    'delivered' => 'text-bg-success',
    'canceled' => 'text-bg-danger',
];
$methodLabels = [
    'pix' => 'Pix',
    'transferencia' => 'Transferência',
    'cartao' => 'Cartão',
    'dinheiro' => 'Dinheiro',
    'outro' => 'Outro',
];
$mainItem = $preorder['items'][0] ?? null;
$progress = (float) $preorder['total'] > 0
    ? min(100, ((float) $preorder['paid'] / (float) $preorder['total']) * 100)
    : 100;
$hasProof = false;
foreach ($payments as $payment) {
    $hasProof = $hasProof || ! empty($payment['proof_path']);
}
$totalQuantity = array_sum(array_map(static fn (array $item): int => (int) $item['quantity'], $preorder['items']));
$arrivedQuantity = array_sum(array_map(static fn (array $item): int => (int) $item['received_quantity'], $preorder['items']));
$arrivalComplete = $totalQuantity > 0 && $arrivedQuantity >= $totalQuantity;
$canRegisterPayment = $preorder['status'] !== 'canceled'
    && $financialEntry !== null
    && (float) $preorder['balance'] > 0;
?>

<main class="dc-content">
    <div class="dc-detail-heading">
        <div>
            <a class="dc-panel-link" href="/pre-vendas">
                <i class="bi bi-arrow-left"></i> Voltar para pré-vendas
            </a>
            <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                <h1 class="dc-page-title">Pré-venda <?= esc($preorder['reference']) ?></h1>
                <span class="badge <?= esc($statusClasses[$preorder['status']] ?? 'text-bg-secondary') ?>">
                    <?= esc($statusLabels[$preorder['status']] ?? $preorder['status']) ?>
                </span>
            </div>
            <p class="dc-page-subtitle">
                Pedido em <?= esc(date('d/m/Y', strtotime($preorder['order_date']))) ?> para
                <?= esc($preorder['seller_name']) ?>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-primary" href="/pre-vendas/<?= $preorder['id'] ?>/editar"><i class="bi bi-pencil"></i> Editar</a>
            <a class="btn btn-outline-primary" href="/entregas/<?= $preorder['id'] ?>">
                <i class="bi bi-box-seam"></i> Gerenciar entrega
            </a>
        </div>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>
    <?php if (session('errors')): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('esc', session('errors'))) ?></div>
    <?php endif ?>

    <div class="dc-detail-grid">
        <section class="card dc-detail-media">
            <?php if (! empty($mainItem['image_id'])): ?>
                <img id="preorder-main-image"
                    class="dc-detail-main-image"
                    src="/imagens/carrinhos/<?= $mainItem['image_id'] ?>"
                    alt="<?= esc($mainItem['product_name']) ?>">
            <?php else: ?>
                <div id="preorder-main-placeholder" class="dc-detail-main-placeholder">
                    <i class="bi bi-car-front-fill"></i>
                    <span>Imagem não cadastrada</span>
                </div>
            <?php endif ?>

            <div class="dc-detail-thumbnails" aria-label="Itens da pré-venda">
                <?php foreach ($preorder['items'] as $index => $item): ?>
                    <button class="dc-detail-thumbnail <?= $index === 0 ? 'active' : '' ?>"
                        type="button"
                        data-image="<?= ! empty($item['image_id']) ? '/imagens/carrinhos/' . $item['image_id'] : '' ?>"
                        data-alt="<?= esc($item['product_name'], 'attr') ?>"
                        title="<?= esc($item['product_name'], 'attr') ?>">
                        <?php if (! empty($item['image_id'])): ?>
                            <img src="/imagens/carrinhos/<?= $item['image_id'] ?>"
                                alt="<?= esc($item['product_name']) ?>">
                        <?php else: ?>
                            <i class="bi bi-car-front"></i>
                        <?php endif ?>
                    </button>
                <?php endforeach ?>
            </div>

            <div class="dc-detail-product-list">
                <?php foreach ($preorder['items'] as $item): ?>
                    <div class="dc-detail-product-row">
                        <div>
                            <strong><?= esc($item['product_name']) ?></strong>
                            <span><?= esc($item['brand']) ?></span>
                        </div>
                        <div class="text-end">
                            <strong><?= (int) $item['quantity'] ?> × R$
                                <?= number_format((float) $item['unit_price'], 2, ',', '.') ?></strong>
                            <span><?= (int) $item['received_quantity'] ?> no vendedor</span>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </section>

        <section class="card card-body dc-financial-summary">
            <h2 class="dc-detail-card-title">Resumo financeiro</h2>
            <div class="dc-summary-row">
                <span>Itens</span>
                <strong>R$ <?= number_format((float) $preorder['items_total'], 2, ',', '.') ?></strong>
            </div>
            <?php if ((float) $preorder['shipping_amount'] > 0): ?>
                <div class="dc-summary-row">
                    <span>Frete</span>
                    <strong>R$ <?= number_format((float) $preorder['shipping_amount'], 2, ',', '.') ?></strong>
                </div>
            <?php endif ?>
            <?php if ((float) $preorder['discount_amount'] > 0): ?>
                <div class="dc-summary-row">
                    <span>Desconto</span>
                    <strong class="text-success">− R$
                        <?= number_format((float) $preorder['discount_amount'], 2, ',', '.') ?></strong>
                </div>
            <?php endif ?>
            <div class="dc-summary-row dc-summary-total">
                <span>Valor total</span>
                <strong>R$ <?= number_format((float) $preorder['total'], 2, ',', '.') ?></strong>
            </div>
            <div class="dc-summary-row">
                <span>Entrada</span>
                <strong>R$ <?= number_format((float) $preorder['down_payment_amount'], 2, ',', '.') ?></strong>
            </div>
            <div class="dc-summary-row">
                <span>Total pago</span>
                <strong class="text-success">R$ <?= number_format((float) $preorder['paid'], 2, ',', '.') ?></strong>
            </div>
            <div class="dc-summary-row dc-summary-balance">
                <span>Saldo a pagar</span>
                <strong>R$ <?= number_format((float) $preorder['balance'], 2, ',', '.') ?></strong>
            </div>
            <div class="dc-payment-progress" aria-label="<?= number_format($progress, 0) ?>% pago">
                <span style="width: <?= number_format($progress, 2, '.', '') ?>%"></span>
            </div>
            <small class="text-secondary"><?= number_format($progress, 0) ?>% do pedido pago</small>
        </section>

        <section class="card card-body dc-delivery-summary">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <h2 class="dc-detail-card-title mb-0">Entrega</h2>
                <a class="dc-panel-link" href="/entregas/<?= $preorder['id'] ?>">Atualizar</a>
            </div>
            <dl class="dc-delivery-list">
                <div>
                    <dt>Data prevista</dt>
                    <dd><?= $preorder['expected_delivery_date']
                        ? esc(date('d/m/Y', strtotime($preorder['expected_delivery_date'])))
                        : 'Não informada' ?></dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>
                        <span class="badge <?= esc($statusClasses[$preorder['status']] ?? 'text-bg-secondary') ?>">
                            <?= esc($statusLabels[$preorder['status']] ?? $preorder['status']) ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt>Vendedor</dt>
                    <dd><?= esc($preorder['seller_name']) ?></dd>
                </div>
                <div>
                    <dt>Código de rastreio</dt>
                    <dd><?= esc($preorder['tracking_code'] ?: 'Não informado') ?></dd>
                </div>
                <div>
                    <dt>Data de recebimento</dt>
                    <dd><?= $preorder['actual_delivery_date']
                        ? esc(date('d/m/Y', strtotime($preorder['actual_delivery_date'])))
                        : '—' ?></dd>
                </div>
            </dl>
            <div class="dc-arrival-box">
                <div>
                    <strong>Chegada ao vendedor</strong>
                    <span><?= $arrivedQuantity ?> de <?= $totalQuantity ?> miniatura(s)</span>
                </div>
                <?php if ($arrivalComplete): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-success">Chegada completa</span>
                        <a class="btn btn-outline-primary btn-sm"
                            href="/entregas/envios/novo?seller_id=<?= $preorder['seller_id'] ?>">
                            Preparar envio
                        </a>
                    </div>
                <?php else: ?>
                    <form method="post" action="/pre-vendas/<?= $preorder['id'] ?>/chegada-completa">
                        <?= csrf_field() ?>
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="bi bi-check2-circle"></i> Marcar chegada completa
                        </button>
                    </form>
                <?php endif ?>
            </div>
            <?php if (! $arrivalComplete): ?>
                <a class="dc-panel-link d-inline-block mt-2" href="/entregas/<?= $preorder['id'] ?>">
                    Registrar chegada parcial por item
                </a>
            <?php endif ?>
            <?php if ($preorder['status'] !== 'canceled'): ?>
                <div class="border-top mt-3 pt-3">
                    <form method="post" action="/pre-vendas/<?= $preorder['id'] ?>/cancelar" onsubmit="return confirm('Cancelar esta pré-venda?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger btn-sm" type="submit">
                            <i class="bi bi-x-circle"></i> Cancelar pré-venda
                        </button>
                    </form>
                </div>
            <?php endif ?>
        </section>
    </div>

    <section class="card dc-detail-lower">
        <div class="dc-detail-tabs" role="tablist" aria-label="Detalhes da pré-venda">
            <button class="active" type="button" role="tab" data-detail-tab="payments">Pagamentos</button>
            <?php if ($canRegisterPayment): ?>
                <button type="button" role="tab" data-detail-tab="register-payment">Registrar pagamento</button>
            <?php endif ?>
            <button type="button" role="tab" data-detail-tab="history">Histórico</button>
            <button type="button" role="tab" data-detail-tab="notes">Observações</button>
            <button type="button" role="tab" data-detail-tab="files">Arquivos</button>
        </div>

        <div class="dc-detail-panel" data-detail-panel="payments">
            <div class="dc-detail-panel-heading">
                <div>
                    <h2>Pagamentos realizados</h2>
                    <p>O sinal e cada parcela ficam registrados separadamente.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Forma de pagamento</th>
                            <th>Comprovante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ((float) $preorder['down_payment_amount'] > 0): ?>
                            <tr>
                                <td><?= esc(date('d/m/Y', strtotime($preorder['order_date']))) ?></td>
                                <td><strong>Entrada</strong></td>
                                <td>R$ <?= number_format((float) $preorder['down_payment_amount'], 2, ',', '.') ?></td>
                                <td><?= esc($methodLabels[$preorder['down_payment_method']] ?? 'Não informada') ?></td>
                                <td><span class="text-secondary">—</span></td>
                            </tr>
                        <?php endif ?>
                        <?php foreach ($payments as $payment): ?>
                            <?php $isRefund = $payment['type'] === 'refund'; ?>
                            <tr>
                                <td><?= esc(date('d/m/Y', strtotime($payment['paid_at']))) ?></td>
                                <td>
                                    <strong><?= $isRefund ? 'Estorno' : 'Parcela' ?></strong>
                                    <?php if (! empty($payment['notes'])): ?>
                                        <small class="d-block text-secondary"><?= esc($payment['notes']) ?></small>
                                    <?php endif ?>
                                </td>
                                <td class="<?= $isRefund ? 'text-danger' : '' ?>">
                                    <?= $isRefund ? '− ' : '' ?>R$
                                    <?= number_format((float) $payment['amount'], 2, ',', '.') ?>
                                </td>
                                <td><?= esc($methodLabels[$payment['method']] ?? ucfirst((string) $payment['method'])) ?></td>
                                <td>
                                    <?php if (! empty($payment['proof_path'])): ?>
                                        <span class="badge text-bg-primary"><i class="bi bi-paperclip"></i> Anexado</span>
                                    <?php else: ?>
                                        <span class="text-secondary">—</span>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <?php if ((float) $preorder['down_payment_amount'] <= 0 && $payments === []): ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">
                                    Nenhum pagamento registrado.
                                </td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($canRegisterPayment): ?>
            <div class="dc-detail-panel" data-detail-panel="register-payment" hidden>
                <div class="dc-detail-panel-heading">
                    <div>
                        <h2>Registrar pagamento</h2>
                        <p>Saldo disponível: R$ <?= number_format((float) $preorder['balance'], 2, ',', '.') ?>.</p>
                    </div>
                </div>
                <form method="post"
                    enctype="multipart/form-data"
                    action="/financeiro/contas/<?= $financialEntry['id'] ?>/pagamento">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="/pre-vendas/<?= $preorder['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="payment-amount">Valor pago</label>
                            <input class="form-control" id="payment-amount" name="amount" type="number" min="0.01"
                                max="<?= number_format((float) $preorder['balance'], 2, '.', '') ?>" step="0.01" required>
                            <div class="form-text">Você pode informar qualquer valor até o saldo atual.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="payment-method">Forma de pagamento</label>
                            <select class="form-select" id="payment-method" name="method" required>
                                <option value="">Selecione</option>
                                <option value="pix">Pix</option>
                                <option value="transferencia">Transferência</option>
                                <option value="cartao">Cartão</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="payment-proof">Comprovante (opcional)</label>
                            <input class="form-control" id="payment-proof" type="file" name="proof"
                                accept="image/jpeg,image/png,image/webp,application/pdf">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="payment-notes">Observações</label>
                            <textarea class="form-control" id="payment-notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary" type="submit">Confirmar pagamento</button>
                    </div>
                </form>
            </div>
        <?php endif ?>

        <div class="dc-detail-panel" data-detail-panel="history" hidden>
            <div class="dc-detail-panel-heading">
                <div>
                    <h2>Histórico do pedido</h2>
                    <p>Movimentações de cadastro, transporte e recebimento.</p>
                </div>
            </div>
            <div class="dc-timeline">
                <?php foreach ($preorder['events'] as $event): ?>
                    <article>
                        <span class="dc-timeline-marker"></span>
                        <div>
                            <strong><?= esc($statusLabels[$event['new_status']] ?? $event['new_status']) ?></strong>
                            <p>
                                <?= $event['received_quantity'] > 0
                                    ? (int) $event['received_quantity'] . ' item(ns) chegaram ao vendedor.'
                                    : 'Status de entrega atualizado.' ?>
                                <?= ! empty($event['notes']) ? ' ' . esc($event['notes']) : '' ?>
                            </p>
                            <time><?= esc(date('d/m/Y H:i', strtotime($event['created_at']))) ?></time>
                        </div>
                    </article>
                <?php endforeach ?>
                <?php foreach ($statusHistory as $event): ?>
                    <article>
                        <span class="dc-timeline-marker"></span>
                        <div>
                            <strong><?= esc($statusLabels[$event['new_status']] ?? $event['new_status']) ?></strong>
                            <p>Pré-venda cadastrada no sistema.</p>
                            <time><?= esc(date('d/m/Y H:i', strtotime($event['created_at']))) ?></time>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        </div>

        <div class="dc-detail-panel" data-detail-panel="notes" hidden>
            <div class="dc-detail-panel-heading">
                <div>
                    <h2>Observações</h2>
                    <p>Informações adicionais registradas no pedido.</p>
                </div>
            </div>
            <div class="dc-note-box">
                <?= ! empty($preorder['notes'])
                    ? nl2br(esc($preorder['notes']))
                    : '<span class="text-secondary">Nenhuma observação cadastrada.</span>' ?>
            </div>
        </div>

        <div class="dc-detail-panel" data-detail-panel="files" hidden>
            <div class="dc-detail-panel-heading">
                <div>
                    <h2>Arquivos</h2>
                    <p>Fotos dos carrinhos e comprovantes vinculados à pré-venda.</p>
                </div>
            </div>
            <div class="dc-file-grid">
                <?php foreach ($preorder['items'] as $item): ?>
                    <?php if (! empty($item['image_id'])): ?>
                        <a href="/imagens/carrinhos/<?= $item['image_id'] ?>" target="_blank" rel="noopener">
                            <img src="/imagens/carrinhos/<?= $item['image_id'] ?>"
                                alt="<?= esc($item['product_name']) ?>">
                            <span><?= esc($item['product_name']) ?></span>
                        </a>
                    <?php endif ?>
                <?php endforeach ?>
                <?php if ($hasProof): ?>
                    <div class="dc-file-private">
                        <i class="bi bi-file-earmark-lock"></i>
                        <span>Comprovante(s) armazenado(s)</span>
                    </div>
                <?php endif ?>
                <?php if (! $hasProof && array_filter(array_column($preorder['items'], 'image_id')) === []): ?>
                    <p class="text-secondary mb-0">Nenhum arquivo vinculado.</p>
                <?php endif ?>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('[data-detail-tab]');
    const panels = document.querySelectorAll('[data-detail-panel]');

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            tabButtons.forEach((item) => item.classList.remove('active'));
            panels.forEach((panel) => panel.hidden = true);
            button.classList.add('active');
            document.querySelector(`[data-detail-panel="${button.dataset.detailTab}"]`).hidden = false;
        });
    });

    const mainImage = document.querySelector('#preorder-main-image');
    const thumbnails = document.querySelectorAll('[data-image]');
    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            thumbnails.forEach((item) => item.classList.remove('active'));
            thumbnail.classList.add('active');
            if (mainImage && thumbnail.dataset.image) {
                mainImage.src = thumbnail.dataset.image;
                mainImage.alt = thumbnail.dataset.alt;
            }
        });
    });
});
</script>

<?= $this->include('partials/footer') ?>
