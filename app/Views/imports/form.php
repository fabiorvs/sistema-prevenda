<?= $this->include('partials/header') ?>
<?php $selectedBrand = (string) old('new_product_brand'); ?>
<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-back-button" href="/importacoes"><i class="bi bi-arrow-left"></i> Voltar para importações</a>
            <h1 class="dc-page-title mt-2">Nova importação</h1>
            <p class="dc-page-subtitle">Controle a miniatura internacional até ela entrar na coleção.</p>
        </div>
    </div>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('esc', session('errors'))) ?></div>
    <?php endif ?>

    <form class="card" method="post" enctype="multipart/form-data" action="/importacoes">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Carrinho já cadastrado</label>
                    <select class="form-select" name="product_id">
                        <option value="">Cadastrar novo abaixo</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>"><?= esc($product['brand'] . ' — ' . $product['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Loja / fornecedor</label><input class="form-control" name="supplier_name" value="<?= esc(old('supplier_name')) ?>" required></div>
                <div class="col-md-3"><label class="form-label">Data da compra</label><input class="form-control" type="date" name="purchased_at" value="<?= esc(old('purchased_at', date('Y-m-d'))) ?>" required></div>
                <div class="col-md-3">
                    <label class="form-label" for="new_product_brand">Marca</label>
                    <select class="form-select" id="new_product_brand" name="new_product_brand">
                        <option value="">Selecione uma marca</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= esc($brand['brand'], 'attr') ?>" <?= $selectedBrand === $brand['brand'] ? 'selected' : '' ?>><?= esc($brand['brand']) ?></option>
                        <?php endforeach ?>
                        <option value="__new__" <?= $selectedBrand === '__new__' ? 'selected' : '' ?>>+ Nova marca</option>
                    </select>
                    <input class="form-control mt-2" id="custom_product_brand" name="custom_product_brand" placeholder="Digite a nova marca" value="<?= esc(old('custom_product_brand')) ?>" <?= $selectedBrand === '__new__' ? '' : 'hidden' ?>>
                </div>
                <div class="col-md-4"><label class="form-label">Modelo novo</label><input class="form-control" name="new_product_name" value="<?= esc(old('new_product_name')) ?>"></div>
                <div class="col-md-2"><label class="form-label">Escala</label><input class="form-control" name="new_product_scale" value="<?= esc(old('new_product_scale')) ?>" placeholder="1:64"></div>
                <div class="col-md-3"><label class="form-label">Foto</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
                <div class="col-md-2"><label class="form-label">Moeda</label><select class="form-select" name="currency"><option value="USD">USD</option><option value="EUR">EUR</option><option value="JPY">JPY</option><option value="BRL">BRL</option></select></div>
                <div class="col-md-2"><label class="form-label">Cotação (R$)</label><input class="form-control" name="exchange_rate" type="number" min="0.0001" step="0.0001" value="<?= esc(old('exchange_rate', '1')) ?>" required></div>
                <div class="col-md-2"><label class="form-label">Item (moeda)</label><input class="form-control" name="item_amount" type="number" min="0.01" step="0.01" value="<?= esc(old('item_amount')) ?>" required></div>
                <div class="col-md-2"><label class="form-label">Frete (moeda)</label><input class="form-control" name="shipping_amount" type="number" min="0" step="0.01" value="<?= esc(old('shipping_amount', '0')) ?>"></div>
                <div class="col-md-2"><label class="form-label">Taxas (moeda)</label><input class="form-control" name="tax_amount" type="number" min="0" step="0.01" value="<?= esc(old('tax_amount', '0')) ?>"></div>
                <div class="col-md-2"><label class="form-label">Previsão de chegada</label><input class="form-control" type="date" name="expected_at" value="<?= esc(old('expected_at')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Transportadora</label><input class="form-control" name="carrier" value="<?= esc(old('carrier')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Código de rastreio</label><input class="form-control" name="tracking_code" value="<?= esc(old('tracking_code')) ?>"></div>
                <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="purchased">Compra confirmada</option><option value="shipped">Em trânsito</option><option value="customs">Em fiscalização</option></select></div>
                <div class="col-md-3"><label class="form-label">Localização ao receber</label><input class="form-control" name="location" value="<?= esc(old('location')) ?>" placeholder="Estante, caixa..."></div>
                <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="notes" rows="3"><?= esc(old('notes')) ?></textarea></div>
            </div>
        </div>
        <div class="card-body border-top text-end"><button class="btn btn-primary">Cadastrar importação</button></div>
    </form>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('new_product_brand');
    const custom = document.getElementById('custom_product_brand');
    const update = () => { custom.hidden = select.value !== '__new__'; if (!custom.hidden) custom.focus(); };
    select.addEventListener('change', update);
    update();
});
</script>
<?= $this->include('partials/footer') ?>
