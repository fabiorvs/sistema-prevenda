<?= $this->include('partials/header') ?>
<main class="dc-content">
    <div class="dc-page-heading"><div><a class="dc-back-button" href="/carrinhos"><i class="bi bi-arrow-left"></i> Voltar para carrinhos</a><h1 class="dc-page-title mt-2"><?= $product ? 'Editar carrinho' : 'Novo carrinho' ?></h1><p class="dc-page-subtitle">Informações, foto principal e galeria do modelo.</p></div></div>
    <?php if (session('errors')): ?><div class="alert alert-danger"><?= implode('<br>', array_map('esc', session('errors'))) ?></div><?php endif ?>
    <form method="post" action="<?= $product ? '/carrinhos/' . $product['id'] : '/carrinhos' ?>" enctype="multipart/form-data" class="card">
        <?= csrf_field() ?>
        <div class="card-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label">Marca</label><input class="form-control" name="brand" required value="<?= esc(old('brand', $product['brand'] ?? '')) ?>"></div>
            <div class="col-md-8"><label class="form-label">Modelo</label><input class="form-control" name="name" required value="<?= esc(old('name', $product['name'] ?? '')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Código do fabricante</label><input class="form-control" name="manufacturer_code" value="<?= esc(old('manufacturer_code', $product['manufacturer_code'] ?? '')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Escala</label><input class="form-control" name="scale" placeholder="Ex.: 1:64" value="<?= esc(old('scale', $product['scale'] ?? '')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Série</label><input class="form-control" name="series" value="<?= esc(old('series', $product['series'] ?? '')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Nova foto principal</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp"><small class="text-secondary">JPEG, PNG ou WebP, até 5 MB.</small></div>
            <div class="col-md-6"><label class="form-label">Adicionar à galeria</label><input class="form-control" type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp"><small class="text-secondary">Você pode selecionar várias fotos.</small></div>
            <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="notes" rows="3"><?= esc(old('notes', $product['notes'] ?? '')) ?></textarea></div>
        </div></div>
        <div class="card-body border-top text-end"><button class="btn btn-primary">Salvar carrinho</button></div>
    </form>
    <?php if ($product): ?>
        <section class="card mt-3"><div class="card-body border-bottom"><h2 class="dc-panel-title mb-0">Galeria de fotos</h2></div><div class="card-body"><div class="row g-3">
            <?php foreach ($images as $image): ?><div class="col-6 col-md-3"><div class="dc-product-gallery-image"><img src="/imagens/carrinhos/<?= $image['id'] ?>" alt="<?= esc($product['name']) ?>"><?php if ((int) $image['is_primary'] === 1): ?><span class="badge text-bg-primary">Principal</span><?php endif ?><form method="post" action="/carrinhos/<?= $product['id'] ?>/imagens/<?= $image['id'] ?>/excluir"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover esta foto?')"><i class="bi bi-trash"></i> Remover</button></form></div></div><?php endforeach ?>
            <?php if ($images === []): ?><p class="text-secondary mb-0">Nenhuma foto cadastrada.</p><?php endif ?>
        </div></div></section>
    <?php endif ?>
</main>
<?= $this->include('partials/footer') ?>
