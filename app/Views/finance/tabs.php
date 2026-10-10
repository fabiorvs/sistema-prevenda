<nav class="dc-tabs mb-4" aria-label="Navegação financeira">
    <a class="<?= ($financeTab ?? '') === 'summary' ? 'active' : '' ?>" href="/financeiro#resumo">Resumo</a>
    <a class="<?= ($financeTab ?? '') === 'accounts' ? 'active' : '' ?>" href="/financeiro">Contas a pagar</a>
    <a class="<?= ($financeTab ?? '') === 'payments' ? 'active' : '' ?>" href="/financeiro/pagamentos">Pagamentos</a>
    <a href="/garagem">Garagem</a>
</nav>
