<?= $this->include('partials/header') ?>
<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-back-button" href="/garagem">
                <i class="bi bi-arrow-left"></i> Voltar para garagem
            </a>
            <h1 class="dc-page-title mt-2">Editar item da garagem</h1>
            <p class="dc-page-subtitle">Corrija os dados ou substitua a foto principal da miniatura.</p>
        </div>
    </div>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', array_map('esc', session('errors'))) ?>
        </div>
    <?php endif ?>

    <form class="card" method="post" enctype="multipart/form-data" action="/garagem/<?= $item['id'] ?>/editar">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="seller_id">Vendedor</label>
                    <select class="form-select" id="seller_id" name="seller_id" required>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?= $seller['id'] ?>" <?= (int) $item['seller_id'] === (int) $seller['id'] ? 'selected' : '' ?>>
                                <?= esc($seller['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="unit_price">Valor unitário</label>
                    <input class="form-control" id="unit_price" name="unit_price" type="number" step="0.01" min="0.01" value="<?= esc(old('unit_price', $item['unit_price'])) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="acquired_at">Data da compra</label>
                    <input class="form-control" id="acquired_at" name="acquired_at" type="date" value="<?= esc(old('acquired_at', $item['acquired_at'])) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="next_payment_date">Próxima parcela</label>
                    <input class="form-control" id="next_payment_date" name="next_payment_date" type="date" value="<?= esc(old('next_payment_date', $item['next_payment_date'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="image">Nova foto principal</label>
                    <input class="form-control" id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment">
                    <p class="form-text mb-0">No celular, abre a câmera traseira. JPEG, PNG ou WebP, até 5 MB.</p>
                </div>
                <div class="col-md-4">
                    <?php if ($item['image_id']): ?>
                        <label class="form-label d-block">Foto atual</label>
                        <img class="rounded border" src="/imagens/carrinhos/<?= $item['image_id'] ?>" alt="<?= esc($item['product_name']) ?>" style="width: 96px; height: 72px; object-fit: contain;">
                    <?php endif ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Observações</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= esc(old('notes', $item['notes'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-body border-top text-end">
            <button class="btn btn-primary">Salvar alterações</button>
        </div>
    </form>
</main>
<?= $this->include('partials/footer') ?>
