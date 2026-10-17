<?= $this->include('partials/header') ?>
<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <h1 class="dc-page-title">Vendedores</h1>
            <p class="dc-page-subtitle">Gerencie seus vendedores e lojas.</p>
        </div><a class="btn btn-primary" href="/vendedores/novo">+ Novo vendedor</a>
    </div><?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?><form class="mb-3"><input class="form-control" name="q" value="<?= esc($q) ?>"
            placeholder="Pesquisar vendedor..."></form>
    <div class="row g-3"><?php foreach ($sellers as $seller): ?><div class="col-sm-6 col-lg-4">
            <article class="card h-100 overflow-hidden"><?php if (!empty($seller['image_path'])): ?><img
                    class="dc-seller-image" src="/imagens/vendedores/<?= $seller['id'] ?>"
                    alt="Logo de <?= esc($seller['name']) ?>"><?php else: ?><div class="dc-seller-placeholder">🏪</div>
                <?php endif ?><div class="card-body">
                    <h2 class="h5 mb-1"><?= esc($seller['name']) ?></h2>
                    <p class="mb-1 text-secondary"><?= esc($seller['contact_name'] ?? '') ?></p>
                    <?php if ($seller['instagram']): ?><small
                        class="text-secondary d-block mb-3">@<?= esc(ltrim($seller['instagram'], '@')) ?></small><?php endif ?><a
                        class="btn btn-sm btn-outline-primary" href="/vendedores/<?= $seller['id'] ?>/editar">Editar</a>
                    <form method="post" action="/vendedores/<?= $seller['id'] ?>/excluir" class="d-inline">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Excluir este vendedor?')">Excluir</button></form>
                </div>
            </article>
        </div><?php endforeach ?></div><?php if ($sellers === []): ?><div class="text-center text-secondary py-5">Nenhum
        vendedor encontrado.</div><?php endif ?><div class="dc-pagination mt-4">
        <?= $pager->links('default', 'diecast') ?></div>
</main><?= $this->include('partials/footer') ?>
