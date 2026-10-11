<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= esc($title ?? 'Acesso') ?> | Diecast Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('css/auth.css') ?>">
</head>
<body>
<main class="auth-shell">
    <aside class="auth-brand" aria-label="Apresentação do Diecast Manager">
        <a class="brand-lockup" href="<?= site_url('/') ?>"><span class="brand-symbol"><i class="bi bi-car-front-fill"></i></span><span>Diecast <span class="brand-muted">Manager</span></span></a>
        <div class="brand-content">
            <div class="brand-pill"><span class="pill-dot"></span> Seu acervo, sob controle</div>
            <h1>Organize sua coleção.<br><em>Planeje cada conquista.</em></h1>
            <p>Pré-vendas, pagamentos e entregas em um só lugar. Mais controle para sua próxima miniatura.</p>
            <div class="brand-stats"><div><strong>01</strong><span>Organize</span></div><div><strong>02</strong><span>Acompanhe</span></div><div><strong>03</strong><span>Colecione</span></div></div>
        </div>
        <div class="brand-footer">DIECAST MANAGER <span>●</span> SUA COLEÇÃO EM MOVIMENTO</div>
        <div class="brand-orbit orbit-one"></div><div class="brand-orbit orbit-two"></div>
    </aside>
    <section class="auth-panel">
        <div class="mobile-brand"><span class="brand-symbol"><i class="bi bi-car-front-fill"></i></span><span>Diecast <strong>Manager</strong></span></div>
        <div class="auth-content">
            <?php if (session('error') !== null): ?>
                <div class="alert alert-error" role="alert"><?= esc(session('error')) ?></div>
            <?php endif ?>
            <?php if (session('message') !== null): ?>
                <div class="alert alert-success" role="status"><?= esc(session('message')) ?></div>
            <?php endif ?>
            <?php if (session('errors') !== null): ?>
                <div class="alert alert-error" role="alert"><ul><?php foreach ((array) session('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div>
            <?php endif ?>
            <?= $this->renderSection('content') ?>
        </div>
        <footer class="auth-bottom">© <?= date('Y') ?> Diecast Manager <span>•</span> Feito para colecionadores</footer>
    </section>
</main>
</body>
</html>
