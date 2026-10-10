<?= $this->include('partials/header') ?>
<?php $oldQuantities = (array) old('quantity'); ?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-panel-link" href="/entregas">
                <i class="bi bi-arrow-left"></i> Voltar para entregas
            </a>
            <h1 class="dc-page-title mt-2">Organizar envio</h1>
            <p class="dc-page-subtitle">Agrupe miniaturas pagas e disponíveis no mesmo vendedor.</p>
        </div>
    </div>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', array_map('esc', session('errors'))) ?>
        </div>
    <?php endif ?>

    <form class="card card-body mb-4" method="get" action="/entregas/envios/novo">
        <div class="row g-3 align-items-end">
            <div class="col-md-9">
                <label class="form-label" for="seller-filter">Vendedor</label>
                <select class="form-select" id="seller-filter" name="seller_id" required>
                    <option value="">Selecione para ver as miniaturas disponíveis</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?= $seller['id'] ?>" <?= $sellerId === (int) $seller['id'] ? 'selected' : '' ?>>
                            <?= esc($seller['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary w-100" type="submit">Carregar miniaturas</button>
            </div>
        </div>
    </form>

    <?php if ($sellerId > 0): ?>
        <form method="post" action="/entregas/envios" class="card">
            <?= csrf_field() ?>
            <input type="hidden" name="seller_id" value="<?= $sellerId ?>">

            <div class="card-body">
                <div class="dc-detail-panel-heading">
                    <div>
                        <h2>Miniaturas disponíveis</h2>
                        <p>São exibidos itens pagos e disponíveis neste vendedor, vindos de pré-vendas e da garagem.</p>
                    </div>
                    <?php if ($items !== []): ?>
                        <button class="btn btn-light btn-sm" id="select-all-items" type="button">Selecionar todas</button>
                    <?php endif ?>
                </div>

                <?php if ($items !== []): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th aria-label="Selecionar"></th>
                                    <th>Miniatura</th>
                                    <th>Origem</th>
                                    <th>Pagamento</th>
                                    <th>Disponível</th>
                                    <th>Enviar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <?php $wasSelected = array_key_exists($item['key'], $oldQuantities); ?>
                                    <tr>
                                        <td>
                                            <input class="form-check-input shipment-item-check"
                                                type="checkbox"
                                                value="<?= $item['id'] ?>"
                                                aria-label="Selecionar <?= esc($item['product_name'], 'attr') ?>"
                                                <?= $wasSelected ? 'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($item['image_id']): ?>
                                                    <img class="dc-table-thumb"
                                                        src="/imagens/carrinhos/<?= $item['image_id'] ?>"
                                                        alt="<?= esc($item['product_name']) ?>">
                                                <?php else: ?>
                                                    <span class="dc-table-thumb dc-table-thumb-empty">
                                                        <i class="bi bi-car-front"></i>
                                                    </span>
                                                <?php endif ?>
                                                <div>
                                                    <strong><?= esc($item['product_name']) ?></strong>
                                                    <small class="d-block text-secondary">
                                                        <?= esc($item['brand']) ?><?= $item['scale'] ? ' · ' . esc($item['scale']) : '' ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="dc-panel-link" href="<?= esc($item['source_url'], 'attr') ?>">
                                                <?= esc($item['source_label']) ?> · <?= esc($item['reference']) ?>
                                            </a>
                                        </td>
                                        <td><span class="badge text-bg-success">Pago</span></td>
                                        <td><?= (int) $item['available_quantity'] ?></td>
                                        <td>
                                            <input class="form-control shipment-item-quantity"
                                                type="number"
                                                min="1"
                                                max="<?= (int) $item['available_quantity'] ?>"
                                                name="quantity[<?= esc($item['key'], 'attr') ?>]"
                                                value="<?= esc($oldQuantities[$item['key']] ?? 1) ?>"
                                                <?= $wasSelected ? '' : 'disabled' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>

                    <hr class="my-4">
                    <h2 class="dc-panel-title mb-3">Dados do envio</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="carrier">Transportadora ou empresa</label>
                            <input class="form-control"
                                id="carrier"
                                name="carrier"
                                placeholder="Correios, Jadlog, transportadora..."
                                value="<?= esc(old('carrier')) ?>"
                                required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="shipping_amount">Valor do frete</label>
                            <input class="form-control"
                                id="shipping_amount"
                                name="shipping_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                value="<?= esc(old('shipping_amount', 0)) ?>"
                                required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="freight_split">Ratear frete por</label>
                            <select class="form-select" id="freight_split" name="freight_split" required>
                                <option value="quantity" <?= old('freight_split', 'quantity') === 'quantity' ? 'selected' : '' ?>>Quantidade</option>
                                <option value="value" <?= old('freight_split') === 'value' ? 'selected' : '' ?>>Valor dos itens</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="tracking_code">Código de rastreio</label>
                            <input class="form-control"
                                id="tracking_code"
                                name="tracking_code"
                                value="<?= esc(old('tracking_code')) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="shipped_at">Data do envio</label>
                            <input class="form-control"
                                id="shipped_at"
                                name="shipped_at"
                                type="date"
                                value="<?= esc(old('shipped_at')) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="expected_delivery_date">Previsão de entrega</label>
                            <input class="form-control"
                                id="expected_delivery_date"
                                name="expected_delivery_date"
                                type="date"
                                value="<?= esc(old('expected_delivery_date')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">Observações</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"><?= esc(old('notes')) ?></textarea>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-box-seam fs-1 text-secondary"></i>
                        <h2 class="h6 mt-3">Nenhuma miniatura disponível</h2>
                        <p class="text-secondary small mb-0">
                            Confirme o pagamento completo e marque a chegada ao vendedor na pré-venda; itens quitados da garagem também aparecem aqui.
                        </p>
                    </div>
                <?php endif ?>
            </div>

            <?php if ($items !== []): ?>
                <div class="card-body border-top d-flex justify-content-end gap-2">
                    <a class="btn btn-outline-secondary" href="/entregas">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Criar envio agrupado</button>
                </div>
            <?php endif ?>
        </form>
    <?php endif ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = [...document.querySelectorAll('.shipment-item-check')];
    const selectAll = document.getElementById('select-all-items');

    const syncQuantity = (checkbox) => {
        const quantity = checkbox.closest('tr').querySelector('.shipment-item-quantity');
        quantity.disabled = ! checkbox.checked;
        if (checkbox.checked && Number(quantity.value) < 1) {
            quantity.value = 1;
        }
    };

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', () => syncQuantity(checkbox));
        syncQuantity(checkbox);
    });

    selectAll?.addEventListener('click', () => {
        const shouldSelect = checkboxes.some((checkbox) => ! checkbox.checked);
        checkboxes.forEach((checkbox) => {
            checkbox.checked = shouldSelect;
            syncQuantity(checkbox);
        });
        selectAll.textContent = shouldSelect ? 'Limpar seleção' : 'Selecionar todas';
    });
});
</script>

<?= $this->include('partials/footer') ?>
