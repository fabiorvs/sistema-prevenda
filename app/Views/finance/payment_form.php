<?= $this->include('partials/header') ?>

<main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-panel-link" href="<?= esc($returnTo, 'attr') ?>">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <h1 class="dc-page-title mt-2">Registrar pagamento</h1>
            <p class="dc-page-subtitle">
                <?= esc($entry['description']) ?> · Saldo atual: R$
                <?= number_format((float) $entry['remaining'], 2, ',', '.') ?>
            </p>
        </div>
    </div>

    <?php if (session('errors')): ?>
        <div class="alert alert-danger"><?= implode('<br>', session('errors')) ?></div>
    <?php endif ?>

    <form method="post"
        enctype="multipart/form-data"
        action="/financeiro/contas/<?= $entry['id'] ?>/pagamento"
        class="card card-body">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="<?= esc($returnTo, 'attr') ?>">

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="payment-amount">Valor pago</label>
                <input id="payment-amount"
                    class="form-control"
                    name="amount"
                    type="number"
                    min="0.01"
                    max="<?= esc(number_format((float) $entry['remaining'], 2, '.', '')) ?>"
                    step="0.01"
                    value="<?= esc(old('amount')) ?>"
                    required>
                <div class="form-text">Você pode informar qualquer valor até o saldo atual.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="payment-method">Forma de pagamento</label>
                <select id="payment-method" class="form-select" name="method" required>
                    <option value="">Selecione</option>
                    <option value="pix" <?= old('method') === 'pix' ? 'selected' : '' ?>>Pix</option>
                    <option value="transferencia" <?= old('method') === 'transferencia' ? 'selected' : '' ?>>
                        Transferência
                    </option>
                    <option value="cartao" <?= old('method') === 'cartao' ? 'selected' : '' ?>>Cartão</option>
                    <option value="dinheiro" <?= old('method') === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                    <option value="outro" <?= old('method') === 'outro' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="payment-proof">Comprovante (opcional)</label>
                <input id="payment-proof"
                    class="form-control"
                    type="file"
                    name="proof"
                    accept="image/jpeg,image/png,image/webp,application/pdf">
            </div>
            <div class="col-12">
                <label class="form-label" for="payment-notes">Observações</label>
                <textarea id="payment-notes" class="form-control" name="notes" rows="3"><?= esc(old('notes')) ?></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a class="btn btn-light" href="<?= esc($returnTo, 'attr') ?>">Cancelar</a>
            <button class="btn btn-primary" type="submit">Confirmar pagamento</button>
        </div>
    </form>
</main>

<?= $this->include('partials/footer') ?>
