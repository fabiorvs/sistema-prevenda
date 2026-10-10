<?= $this->include('partials/header') ?>
<?php $selectedBrand = (string) old('new_product_brand'); ?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-back-button" href="/garagem">
                <i class="bi bi-arrow-left"></i> Voltar para garagem
            </a>
            <h1 class="dc-page-title mt-2">Novo item na garagem</h1>
            <p class="dc-page-subtitle">Cadastre uma miniatura que já está disponível no vendedor.</p>
        </div>
    </div>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', array_map('esc', session('errors'))) ?>
        </div>
    <?php endif ?>

    <form method="post" enctype="multipart/form-data" action="/garagem" class="card">
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
                    <select class="form-select" id="product_id" name="product_id">
                        <option value="">Cadastrar um novo abaixo</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>"
                                <?= old('product_id') == $product['id'] ? 'selected' : '' ?>>
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
                        <select class="form-select" id="new_product_brand" name="new_product_brand">
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
                            name="custom_product_brand"
                            placeholder="Digite a nova marca"
                            value="<?= esc(old('custom_product_brand')) ?>"
                            <?= $selectedBrand === '__new__' ? '' : 'hidden' ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="new_product_name">Modelo</label>
                        <input class="form-control"
                            id="new_product_name"
                            name="new_product_name"
                            value="<?= esc(old('new_product_name')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="new_product_scale">Escala</label>
                        <input class="form-control"
                            id="new_product_scale"
                            name="new_product_scale"
                            placeholder="1:64"
                            value="<?= esc(old('new_product_scale')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="image">Foto</label>
                        <input class="form-control"
                            id="image"
                            type="file"
                            name="image"
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
                        type="number"
                        min="1"
                        name="quantity"
                        value="<?= esc(old('quantity', 1)) ?>"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="unit_price">Valor unitário</label>
                    <input class="form-control"
                        id="unit_price"
                        type="number"
                        min="0.01"
                        step="0.01"
                        name="unit_price"
                        value="<?= esc(old('unit_price')) ?>"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="initial_payment">Pagamento inicial</label>
                    <input class="form-control"
                        id="initial_payment"
                        type="number"
                        min="0"
                        step="0.01"
                        name="initial_payment"
                        value="<?= esc(old('initial_payment', 0)) ?>">
                </div>
                <div class="col-md-2" id="garage-payment-method-field">
                    <label class="form-label" for="payment_method">Forma</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="pix" <?= old('payment_method', 'pix') === 'pix' ? 'selected' : '' ?>>Pix</option>
                        <option value="transferencia" <?= old('payment_method') === 'transferencia' ? 'selected' : '' ?>>Transferência</option>
                        <option value="cartao" <?= old('payment_method') === 'cartao' ? 'selected' : '' ?>>Cartão</option>
                        <option value="dinheiro" <?= old('payment_method') === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                        <option value="outro" <?= old('payment_method') === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="acquired_at">Data da compra</label>
                    <input class="form-control"
                        id="acquired_at"
                        type="date"
                        name="acquired_at"
                        value="<?= esc(old('acquired_at', date('Y-m-d'))) ?>"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="next_payment_date">Próxima parcela</label>
                    <input class="form-control"
                        id="next_payment_date"
                        type="date"
                        name="next_payment_date"
                        value="<?= esc(old('next_payment_date')) ?>">
                    <div class="form-text">Informe se ainda houver saldo a pagar.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Observações</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= esc(old('notes')) ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-body border-top d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="/garagem">Cancelar</a>
            <button class="btn btn-primary" type="submit">Adicionar à garagem</button>
        </div>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const productSelect = document.getElementById('product_id');
    const newProductFields = document.getElementById('new-product-fields');
    const brandSelect = document.getElementById('new_product_brand');
    const customBrand = document.getElementById('custom_product_brand');
    const initialPayment = document.getElementById('initial_payment');
    const paymentMethodField = document.getElementById('garage-payment-method-field');
    const paymentMethod = document.getElementById('payment_method');

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
        const hasPayment = Number(initialPayment.value) > 0;
        paymentMethodField.hidden = ! hasPayment;
        paymentMethod.required = hasPayment;
    };

    productSelect.addEventListener('change', updateNewProductFields);
    brandSelect.addEventListener('change', updateBrandField);
    initialPayment.addEventListener('input', updatePaymentMethod);
    updateNewProductFields();
    updateBrandField();
    updatePaymentMethod();
});
</script>

<?= $this->include('partials/footer') ?>
