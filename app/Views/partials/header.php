<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Diecast Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/diecast.css" rel="stylesheet">
</head>

<body>
    <div class="dc-shell">
        <aside class="dc-sidebar"><a class="dc-brand" href="/">🚘 Diecast Manager</a>
            <nav class="dc-nav"><a href="/">⌂ &nbsp; Dashboard</a><a href="/pre-vendas">◷ &nbsp; Pré-vendas</a><a
                    href="/vendedores">♙ &nbsp; Vendedores</a><a href="/carrinhos">▣ &nbsp; Carrinhos</a><a
                    href="/financeiro">◉ &nbsp; Financeiro</a><a href="#" class="disabled" aria-disabled="true">▤ &nbsp;
                    Entregas</a></nav>
        </aside>
        <div class="dc-main">
            <header class="dc-topbar"><span>♧</span><span class="dc-user">Olá,
                    <?= esc(auth()->user()->username ?? 'Colecionador') ?></span><a
                    class="btn btn-outline-secondary btn-sm" href="<?= url_to('logout') ?>">Sair</a></header>