<?= $this->include('partials/header') ?>
<?php
$oldProductIds = (array) old('product_id');
$oldBrands = (array) old('new_product_brand');
$oldCustomBrands = (array) old('custom_product_brand');
$oldNames = (array) old('new_product_name');
$oldScales = (array) old('new_product_scale');
$oldQuantities = (array) old('quantity');
$oldPrices = (array) old('unit_price');
$selectedProductId = (string) ($oldProductIds[0] ?? '');
$selectedBrand = (string) ($oldBrands[0] ?? '');
?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-panel-link" href="/pre-vendas">
                <i class="bi bi-arrow-left"></i> Voltar para pré-vendas
            </a>
            <h1 class="dc-page-title mt-2">Nova pré-venda</h1>
            <p class="dc-page-subtitle">Cadastre o pedido, o carrinho e o sinal em uma única tela.</p>
        </div>
    </div>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', array_map('esc', session('errors'))) ?>
        </div>
    <?php endif ?>

    <form method="post" enctype="multipart/form-data" action="/pre-vendas" class="card">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="seller_id">Vendedor</label>
                    <select class="form-select" id="seller_id" name="seller_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= $seller['id'] ?>"
                                <?= old('seller_id') == $seller['id'] ? 'selected' : '' ?>>
                                <?= esc($seller['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="product_id">Carrinho já cadastrado</label>
                    <select class="form-select" id="product_id" name="product_id[]">
                        <option value="">Cadastrar um novo abaixo</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>"
                                <?= $selectedProductId === (string) $product['id'] ? 'selected' : '' ?>>
                                <?= esc($product['brand'] . ' — ' . $product['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <section id="new-product-fields" class="border rounded-3 p-3 mt-3">
                <h2 class="dc-panel-title mb-3">Novo carrinho</h2>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="new_product_brand">Marca</label>
                        <select class="form-select" id="new_product_brand" name="new_product_brand[]">
                            <option value="">Selecione uma marca</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= esc($brand['brand'], 'attr') ?>"
                                    <?= $selectedBrand === $brand['brand'] ? 'selected' : '' ?>>
                                    <?= esc($brand['brand']) ?>
                                </option>
                            <?php endforeach ?>
                            <option value="__new__" <?= $selectedBrand === '__new__' ? 'selected' : '' ?>>
                                + Nova marca
                            </option>
                        </select>
                        <input class="form-control mt-2"
                            id="custom_product_brand"
                            name="custom_product_brand[]"
                            placeholder="Digite a nova marca"
                            value="<?= esc($oldCustomBrands[0] ?? '') ?>"
                            <?= $selectedBrand === '__new__' ? '' : 'hidden' ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="new_product_name">Modelo</label>
                        <input class="form-control"
                            id="new_product_name"
                            name="new_product_name[]"
                            placeholder="Skyline GT-R"
                            value="<?= esc($oldNames[0] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="new_product_scale">Escala</label>
                        <input class="form-control"
                            id="new_product_scale"
                            name="new_product_scale[]"
                            placeholder="1:64"
                            value="<?= esc($oldScales[0] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="new_product_image">Foto</label>
                        <input class="form-control"
                            id="new_product_image"
                            type="file"
                            name="new_product_image"
                            accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>
            </section>

            <hr class="my-4">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label" for="quantity">Quantidade</label>
                    <input class="form-control"
                        id="quantity"
                        name="quantity[]"
                        type="number"
                        min="1"
                        value="<?= esc($oldQuantities[0] ?? 1) ?>"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="unit_price">Valor unitário</label>
                    <input class="form-control"
                        id="unit_price"
                        name="unit_price[]"
                        type="number"
                        min="0.01"
                        step="0.01"
                        value="<?= esc($oldPrices[0] ?? '') ?>"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="order_date">Data do pedido</label>
                    <input class="form-control"
                        id="order_date"
                        type="date"
                        name="order_date"
                        value="<?= esc(old('order_date', date('Y-m-d'))) ?>"
                        required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="expected_delivery_date">Previsão de entrega</label>
                    <input class="form-control"
                        id="expected_delivery_date"
                        type="date"
                        name="expected_delivery_date"
                        value="<?= esc(old('expected_delivery_date')) ?>">
                </div>
            </div>

            <hr class="my-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="down_payment_amount">Sinal pago</label>
                    <input class="form-control"
                        id="down_payment_amount"
                        name="down_payment_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        value="<?= esc(old('down_payment_amount', 0)) ?>">
                </div>
                <div class="col-md-4" id="down-payment-method-field">
                    <label class="form-label" for="down_payment_method">Forma do sinal</label>
                    <select class="form-select" id="down_payment_method" name="down_payment_method">
                        <option value="">Selecione</option>
                        <option value="pix" <?= old('down_payment_method') === 'pix' ? 'selected' : '' ?>>Pix</option>
                        <option value="transferencia" <?= old('down_payment_method') === 'transferencia' ? 'selected' : '' ?>>Transferência</option>
                        <option value="cartao" <?= old('down_payment_method') === 'cartao' ? 'selected' : '' ?>>Cartão</option>
                        <option value="dinheiro" <?= old('down_payment_method') === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                        <option value="outro" <?= old('down_payment_method') === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="notes">Observações</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= esc(old('notes')) ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-body border-top d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="/pre-vendas">Cancelar</a>
            <button class="btn btn-primary" type="submit">Salvar pré-venda</button>
        </div>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const productSelect = document.getElementById('product_id');
    const newProductFields = document.getElementById('new-product-fields');
    const brandSelect = document.getElementById('new_product_brand');
    const customBrand = document.getElementById('custom_product_brand');
    const downPayment = document.getElementById('down_payment_amount');
    const methodField = document.getElementById('down-payment-method-field');
    const methodSelect = document.getElementById('down_payment_method');

    const updateNewProductFields = () => {
        newProductFields.hidden = productSelect.value !== '';
    };
    const updateBrandField = () => {
        customBrand.hidden = brandSelect.value !== '__new__';
        if (! customBrand.hidden) {
            customBrand.focus();
        }
    };
    const updatePaymentMethod = () => {
        const hasDownPayment = Number(downPayment.value) > 0;
        methodField.hidden = ! hasDownPayment;
        methodSelect.required = hasDownPayment;
        if (! hasDownPayment) {
            methodSelect.value = '';
        }
    };

    productSelect.addEventListener('change', updateNewProductFields);
    brandSelect.addEventListener('change', updateBrandField);
    downPayment.addEventListener('input', updatePaymentMethod);
    updateNewProductFields();
    updateBrandField();
    updatePaymentMethod();
});
</script>

<?= $this->include('partials/footer') ?>
