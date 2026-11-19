<?= $this->include('partials/header') ?>
<?php
$isEditing = $purchase !== null;
$selectedBrand = (string) old('new_product_brand');
$selectedProductId = (string) old('product_id', (string) ($purchase['product_id'] ?? ''));
$title = $isEditing ? 'Editar compra presencial' : 'Nova compra presencial';
$subtitle = $isEditing
    ? 'Corrija os dados da compra, da miniatura ou substitua a foto principal.'
    : 'O item será incluído imediatamente na coleção.';
$action = $isEditing ? '/compras/' . $purchase['id'] . '/editar' : '/compras';
$submitLabel = $isEditing ? 'Salvar alterações' : 'Salvar compra e adicionar à coleção';
?>
<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-back-button" href="/compras">
                <i class="bi bi-arrow-left"></i> Voltar para compras
            </a>
            <h1 class="dc-page-title mt-2"><?= esc($title) ?></h1>
            <p class="dc-page-subtitle"><?= esc($subtitle) ?></p>
        </div>
    </div>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', array_map('esc', session('errors'))) ?>
        </div>
    <?php endif ?>

    <form class="card" method="post" enctype="multipart/form-data" action="<?= esc($action) ?>">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="product_id">Miniatura já cadastrada</label>
                    <select class="form-select" id="product_id" name="product_id">
                        <option value="">Cadastrar nova abaixo</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>" <?= $selectedProductId === (string) $product['id'] ? 'selected' : '' ?>>
                                <?= esc($product['brand'] . ' — ' . $product['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <?php if ($isEditing): ?>
                        <p class="form-text mb-0">
                            Para corrigir marca, modelo ou escala de um item existente,
                            <a href="/carrinhos/<?= $purchase['product_id'] ?>/editar">edite o modelo no catálogo</a>.
                        </p>
                    <?php endif ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="store_name">Onde comprou</label>
                    <input class="form-control" id="store_name" name="store_name" value="<?= esc(old('store_name', $purchase['store_name'] ?? '')) ?>" placeholder="Loja, evento, particular..." required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="purchased_at">Data da compra</label>
                    <input class="form-control" id="purchased_at" type="date" name="purchased_at" value="<?= esc(old('purchased_at', $purchase['purchased_at'] ?? date('Y-m-d'))) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="new_product_brand">Marca do novo modelo</label>
                    <select class="form-select" id="new_product_brand" name="new_product_brand">
                        <option value="">Selecione uma marca</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= esc($brand['brand'], 'attr') ?>" <?= $selectedBrand === $brand['brand'] ? 'selected' : '' ?>>
                                <?= esc($brand['brand']) ?>
                            </option>
                        <?php endforeach ?>
                        <option value="__new__" <?= $selectedBrand === '__new__' ? 'selected' : '' ?>>+ Nova marca</option>
                    </select>
                    <input class="form-control mt-2" id="custom_product_brand" name="custom_product_brand" placeholder="Digite a nova marca" value="<?= esc(old('custom_product_brand')) ?>" <?= $selectedBrand === '__new__' ? '' : 'hidden' ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="new_product_name">Modelo novo</label>
                    <input class="form-control" id="new_product_name" name="new_product_name" value="<?= esc(old('new_product_name')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="new_product_scale">Escala</label>
                    <input class="form-control" id="new_product_scale" name="new_product_scale" value="<?= esc(old('new_product_scale')) ?>" placeholder="1:64">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="image"><?= $isEditing ? 'Nova foto principal' : 'Foto' ?></label>
                    <input class="form-control" id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment">
                    <p class="form-text mb-0">
                        No celular, abre a câmera traseira. JPEG, PNG ou WebP, até 5 MB.
                        <?php if ($isEditing): ?>A foto substitui a principal do modelo no catálogo.<?php endif ?>
                    </p>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="quantity">Quantidade</label>
                    <input class="form-control" id="quantity" type="number" min="1" name="quantity" value="<?= esc(old('quantity', $purchase['quantity'] ?? 1)) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="unit_price">Valor unitário</label>
                    <input class="form-control" id="unit_price" type="number" min="0.01" step="0.01" name="unit_price" value="<?= esc(old('unit_price', $purchase['unit_price'] ?? '')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="location">Localização</label>
                    <input class="form-control" id="location" name="location" value="<?= esc(old('location', $purchase['location'] ?? '')) ?>" placeholder="Estante, caixa...">
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Observações</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= esc(old('notes', $purchase['notes'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-body border-top text-end">
            <button class="btn btn-primary"><?= esc($submitLabel) ?></button>
        </div>
    </form>
</main>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('new_product_brand');
        const custom = document.getElementById('custom_product_brand');
        const updateCustomBrandVisibility = () => {
            custom.hidden = select.value !== '__new__';

            if (!custom.hidden) {
                custom.focus();
            }
        };

        select.addEventListener('change', updateCustomBrandVisibility);
        updateCustomBrandVisibility();
    });
</script>
<?= $this->include('partials/footer') ?>
