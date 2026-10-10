<?= $this->include('partials/header') ?>
<main class="dc-content">
    <h1 class="dc-page-title">Registrar pagamento</h1>
    <p class="dc-page-subtitle mb-4"><?= esc($entry['description']) ?> · Valor da conta: R$
        <?= number_format((float) $entry['amount'], 2, ',', '.') ?></p><?php if(session('errors')): ?><div
        class="alert alert-danger"><?= implode('<br>', session('errors')) ?></div><?php endif ?><form method="post"
        enctype="multipart/form-data" action="/financeiro/contas/<?= $entry['id'] ?>/pagamento" class="card card-body">
        <div class="row g-3"><?= csrf_field() ?><div class="col-md-4"><label class="form-label">Valor pago</label><input
                    class="form-control" name="amount" type="number" min="0.01" max="<?= esc($entry['amount']) ?>"
                    step="0.01" required></div>
            <div class="col-md-4"><label class="form-label">Forma de pagamento</label><select class="form-select"
                    name="method" required>
                    <option value="">Selecione</option>
                    <option value="pix">Pix</option>
                    <option value="transferencia">Transferência</option>
                    <option value="cartao">Cartão</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="outro">Outro</option>
                </select></div>
            <div class="col-md-4"><label class="form-label">Comprovante (opcional)</label><input class="form-control"
                    type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf"></div>
            <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control"
                    name="notes"></textarea></div>
        </div><button class="btn btn-primary mt-4">Confirmar pagamento</button>
    </form>
</main><?= $this->include('partials/footer') ?>