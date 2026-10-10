<?= $this->include('partials/header') ?>
<main class="container py-4"><h1 class="h3"><?= $product ? 'Editar carrinho' : 'Novo carrinho' ?></h1>
<?php if (session('errors')): ?><div class="alert alert-danger"><?= implode('<br>', session('errors')) ?></div><?php endif ?>
<form method="post" action="<?= $product ? '/carrinhos/'.$product['id'] : '/carrinhos' ?>" enctype="multipart/form-data" class="card card-body"><?= csrf_field() ?>
<label class="form-label">Marca</label><input class="form-control mb-3" name="brand" required value="<?= esc(old('brand', $product['brand'] ?? '')) ?>">
<label class="form-label">Modelo</label><input class="form-control mb-3" name="name" required value="<?= esc(old('name', $product['name'] ?? '')) ?>">
<label class="form-label">Código do fabricante</label><input class="form-control mb-3" name="manufacturer_code" value="<?= esc(old('manufacturer_code', $product['manufacturer_code'] ?? '')) ?>">
<label class="form-label">Escala</label><input class="form-control mb-3" name="scale" placeholder="Ex.: 1:64" value="<?= esc(old('scale', $product['scale'] ?? '')) ?>">
<label class="form-label">Série</label><input class="form-control mb-3" name="series" value="<?= esc(old('series', $product['series'] ?? '')) ?>">
<label class="form-label">Foto do carrinho</label><input class="form-control mb-3" type="file" name="image" accept="image/jpeg,image/png,image/webp"><small class="text-secondary mb-3">JPEG, PNG ou WebP, até 5 MB.</small>
<label class="form-label">Observações</label><textarea class="form-control mb-3" name="notes"><?= esc(old('notes', $product['notes'] ?? '')) ?></textarea><button class="btn btn-primary">Salvar carrinho</button></form></main><?= $this->include('partials/footer') ?>
