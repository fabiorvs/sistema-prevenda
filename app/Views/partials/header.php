<?php
$currentPath = trim(uri_string(), '/');
$user = auth()->user();
$username = $user->username ?? 'Colecionador';
$initial = mb_strtoupper(mb_substr($username, 0, 1));
$isActive = static function (string $path) use ($currentPath): string {
    if ($path === '') {
        return $currentPath === '' || $currentPath === 'dashboard' ? 'active' : '';
    }

    return $currentPath === $path || str_starts_with($currentPath, $path . '/') ? 'active' : '';
};
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diecast Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/css/diecast.css" rel="stylesheet">
</head>

<body>
    <div class="dc-shell">
        <aside class="dc-sidebar">
            <a class="dc-brand" href="/">
                <span class="dc-brand-mark"><i class="bi bi-car-front-fill"></i></span>
                <span>Diecast Manager</span>
            </a>
            <nav class="dc-nav">
                <a class="<?= $isActive('') ?>" href="/"><i class="bi bi-house-door"></i>Dashboard</a>
                <a class="<?= $isActive('pre-vendas') ?>" href="/pre-vendas"><i class="bi bi-receipt"></i>Pré-vendas</a>
                <a class="<?= $isActive('vendedores') ?>" href="/vendedores"><i class="bi bi-people"></i>Vendedores</a>
                <a class="<?= $isActive('carrinhos') ?>" href="/carrinhos"><i class="bi bi-car-front-fill"></i>Carrinhos</a>
                <a class="<?= $isActive('garagem') ?>" href="/garagem"><i class="bi bi-p-square"></i>Garagem</a>
                <a class="<?= $isActive('colecao') ?>" href="/colecao"><i class="bi bi-grid-3x3-gap"></i>Coleção</a>
                <a class="<?= $isActive('financeiro') ?>" href="/financeiro"><i class="bi bi-wallet2"></i>Financeiro</a>
                <a class="<?= $isActive('entregas') ?>" href="/entregas"><i class="bi bi-box-seam"></i>Entregas</a>
                <a class="<?= $isActive('historico') ?>" href="/historico"><i class="bi bi-clock-history"></i>Histórico</a>
                <?php if ($user?->inGroup('superadmin')): ?>
                    <span class="dc-nav-label">Configurações</span>
                    <a class="<?= $isActive('admin') ?>" href="/admin"><i class="bi bi-shield-lock"></i>Administração</a>
                <?php endif ?>
                <span class="dc-nav-label">Conta</span>
                <a href="<?= url_to('logout') ?>"><i class="bi bi-box-arrow-left"></i>Sair</a>
            </nav>
        </aside>
        <div class="dc-main">
            <header class="dc-topbar">
                <button class="dc-topbar-icon" type="button" aria-label="Notificações">
                    <i class="bi bi-bell"></i>
                </button>
                <span class="dc-avatar" aria-hidden="true"><?= esc($initial) ?></span>
                <span class="dc-user-block">
                    <span class="dc-user"><?= esc($username) ?></span>
                    <span class="dc-user-role"><?= $user?->inGroup('superadmin') ? 'Super Admin' : 'Colecionador' ?></span>
                </span>
            </header>
