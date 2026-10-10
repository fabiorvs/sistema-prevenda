<?= $this->include('partials/header') ?>
<main class="dc-content">
    <div class="mb-4">
        <p class="text-uppercase text-secondary small mb-1">Conta ativa #<?= esc((string) $tenantId) ?></p>
        <h1 class="dc-page-title">Olá, <?= esc($user->username ?? 'colecionador') ?>!</h1>
        <p class="dc-page-subtitle">Organize sua coleção e prepare suas pré-vendas.</p>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <section class="card h-100">
                <div class="dc-stat">
                    <h2 class="h5">Vendedores</h2>
                    <p class="text-secondary">Cadastre lojas, contatos, WhatsApp e redes sociais.</p><a
                        class="btn btn-primary" href="/vendedores">Gerenciar vendedores</a>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="card h-100">
                <div class="dc-stat">
                    <h2 class="h5">Carrinhos</h2>
                    <p class="text-secondary">Monte seu catálogo pessoal, incluindo fotos dos modelos.</p><a
                        class="btn btn-primary" href="/carrinhos">Gerenciar carrinhos</a>
                </div>
            </section>
        </div>
    </div>
</main><?= $this->include('partials/footer') ?>