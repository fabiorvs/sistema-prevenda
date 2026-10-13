<?= $this->include('partials/header') ?>
<main class="dc-content">
    <div class="dc-page-heading">
        <div><h1 class="dc-page-title">Carrinhos</h1><p class="dc-page-subtitle">Seu catálogo pessoal de modelos.</p></div>
        <a class="btn btn-primary" href="/carrinhos/novo"><i class="bi bi-plus-lg me-1"></i> Novo carrinho</a>
    </div>
    <?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif ?>
    <form class="card dc-filter-bar mb-3" method="get" action="/carrinhos">
        <div class="row g-2 align-items-end">
            <div class="col-md-5"><label class="form-label" for="q">Pesquisar</label><input class="form-control" id="q" name="q" value="<?= esc($filters['q']) ?>" placeholder="Modelo, marca ou código..."></div>
            <div class="col-md-3"><label class="form-label" for="brand">Marca</label><select class="form-select" id="brand" name="brand"><option value="">Todas as marcas</option><?php foreach ($brands as $brand): ?><option value="<?= esc($brand['brand'], 'attr') ?>" <?= $filters['brand'] === $brand['brand'] ? 'selected' : '' ?>><?= esc($brand['brand']) ?></option><?php endforeach ?></select></div>
            <div class="col-md-2"><label class="form-label" for="scale">Escala</label><select class="form-select" id="scale" name="scale"><option value="">Todas as escalas</option><?php foreach ($scales as $scale): ?><option value="<?= esc($scale['scale'], 'attr') ?>" <?= $filters['scale'] === $scale['scale'] ? 'selected' : '' ?>><?= esc($scale['scale']) ?></option><?php endforeach ?></select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Filtrar</button></div>
        </div>
    </form>
    <div class="row g-3">
        <?php foreach ($products as $product): ?>
            <div class="col-sm-6 col-lg-3"><article class="card h-100 overflow-hidden">
                <?php if ($product['image_id']): ?><img src="/imagens/carrinhos/<?= $product['image_id'] ?>" class="dc-product-image" alt="<?= esc($product['name']) ?>"><?php else: ?><div class="dc-empty-image"><i class="bi bi-car-front"></i></div><?php endif ?>
                <div class="card-body"><span class="badge text-bg-light"><?= esc($product['brand']) ?></span><h2 class="h6 mt-2 mb-1"><?= esc($product['name']) ?></h2><small class="text-secondary d-block mb-3"><?= esc($product['scale'] ?? '') ?><?= $product['series'] ? ' · ' . esc($product['series']) : '' ?></small><a class="btn btn-sm btn-outline-primary" href="/carrinhos/<?= $product['id'] ?>/editar">Editar</a><form method="post" action="/carrinhos/<?= $product['id'] ?>/excluir" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este carrinho e suas fotos?')">Excluir</button></form></div>
            </article></div>
        <?php endforeach ?>
    </div>
    <?php if ($products === []): ?><div class="text-center text-secondary py-5">Nenhum carrinho encontrado.</div><?php endif ?>
    <div class="dc-pagination mt-4"><?= $pager->links('default', 'diecast') ?></div>
</main>
<?= $this->include('partials/footer') ?>
