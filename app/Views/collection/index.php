<?= $this->include('partials/header') ?>
<main class="dc-content">
    <div class="dc-page-heading"><div><h1 class="dc-page-title">Minha coleção</h1><p class="dc-page-subtitle">Miniaturas recebidas por meio dos seus envios.</p></div></div>
    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-sm-6 col-xl-3"><article class="card h-100">
                <?php if ($item['image_id']): ?><img class="dc-product-image" src="/imagens/carrinhos/<?= $item['image_id'] ?>" alt="<?= esc($item['product_name']) ?>"><?php else: ?><div class="dc-empty-image"><i class="bi bi-car-front"></i></div><?php endif ?>
                <div class="card-body"><h2 class="h6 mb-1"><?= esc($item['product_name']) ?></h2><p class="dc-list-meta"><?= esc($item['brand']) ?><?= $item['scale'] ? ' · ' . esc($item['scale']) : '' ?></p><div class="d-flex justify-content-between small"><span>Recebido</span><strong><?= date('d/m/Y', strtotime($item['received_at'])) ?></strong></div><div class="d-flex justify-content-between small mt-2"><span>Custo final</span><strong>R$ <?= number_format((float) $item['unit_cost'] * (int) $item['quantity'] + (float) $item['freight_amount'], 2, ',', '.') ?></strong></div><form class="mt-3" method="post" action="/colecao/<?= $item['id'] ?>/localizacao"><?= csrf_field() ?><label class="form-label small">Localização</label><input class="form-control form-control-sm" name="location" value="<?= esc($item['location'] ?? '') ?>" placeholder="Estante, caixa, prateleira..."><input type="hidden" name="notes" value="<?= esc($item['notes'] ?? '') ?>"><button class="btn btn-outline-primary btn-sm mt-2">Salvar localização</button></form></div>
            </article></div>
        <?php endforeach ?>
        <?php if ($items === []): ?><div class="col-12"><div class="card card-body text-center text-secondary">Nenhuma miniatura recebida ainda.</div></div><?php endif ?>
    </div>
</main>
<?= $this->include('partials/footer') ?>
