<?= $this->include('partials/header') ?>

<main class="dc-content admin-page">
    <div class="dc-page-heading admin-hero">
        <div>
            <span class="admin-eyebrow"><i class="bi bi-shield-check"></i> Área restrita</span>
            <h1 class="dc-page-title">Central de administração</h1>
            <p class="dc-page-subtitle">Acompanhe a saúde da plataforma, contas, acessos e planos sem expor os dados privados dos colecionadores.</p>
        </div>
        <div class="admin-hero-date"><i class="bi bi-calendar3"></i> <?= date('d/m/Y') ?></div>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= esc(session('message')) ?></div>
    <?php endif ?>
    <?php if (session('error')): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= esc(session('error')) ?></div>
    <?php endif ?>

    <section class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><article class="card dc-stat admin-stat"><span class="dc-stat-icon primary"><i class="bi bi-people"></i></span><p class="dc-stat-label">Usuários cadastrados</p><p class="dc-stat-value"><?= (int) $stats['users'] ?></p><p class="dc-stat-note">Em todas as contas</p></article></div>
        <div class="col-sm-6 col-xl-3"><article class="card dc-stat admin-stat"><span class="dc-stat-icon success"><i class="bi bi-buildings"></i></span><p class="dc-stat-label">Contas ativas</p><p class="dc-stat-value text-success"><?= (int) $stats['tenants'] ?></p><p class="dc-stat-note">+<?= (int) $stats['new_week'] ?> nos últimos 7 dias</p></article></div>
        <div class="col-sm-6 col-xl-3"><article class="card dc-stat admin-stat"><span class="dc-stat-icon warning"><i class="bi bi-car-front"></i></span><p class="dc-stat-label">Miniaturas registradas</p><p class="dc-stat-value"><?= number_format((int) $stats['collection_items'], 0, ',', '.') ?></p><p class="dc-stat-note">Itens já na coleção</p></article></div>
        <div class="col-sm-6 col-xl-3"><article class="card dc-stat admin-stat"><span class="dc-stat-icon <?= (int) $stats['suspended'] > 0 ? 'warning' : 'purple' ?>"><i class="bi bi-activity"></i></span><p class="dc-stat-label">Atenção necessária</p><p class="dc-stat-value <?= (int) $stats['suspended'] > 0 ? 'text-danger' : '' ?>"><?= (int) $stats['suspended'] ?></p><p class="dc-stat-note">Contas suspensas<?= (int) $stats['without_plan'] > 0 ? ' · ' . (int) $stats['without_plan'] . ' sem plano' : '' ?></p></article></div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-xl-8">
            <article class="card h-100 admin-panel">
                <div class="card-body border-bottom d-flex align-items-center justify-content-between gap-3"><div><h2 class="dc-panel-title mb-1">Planos em uso</h2><p class="small text-secondary mb-0">Distribuição atual das contas ativas por plano.</p></div><span class="admin-panel-icon"><i class="bi bi-layers"></i></span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($planUsage as $plan): ?>
                            <div class="col-md-6">
                                <div class="admin-plan-card">
                                    <div class="d-flex justify-content-between align-items-start"><span class="admin-plan-mark"><i class="bi bi-box"></i></span><span class="badge text-bg-light"><?= (int) $plan['user_limit'] ?> usuário(s)</span></div>
                                    <strong class="d-block mt-3"><?= esc($plan['name']) ?></strong>
                                    <span class="text-secondary small"><?= (int) $plan['account_count'] ?> conta(s) utilizando</span>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </article>
        </div>
        <div class="col-xl-4">
            <article class="card h-100 admin-panel">
                <div class="card-body border-bottom"><h2 class="dc-panel-title mb-1">Resumo operacional</h2><p class="small text-secondary mb-0">Leitura rápida do ambiente.</p></div>
                <div class="list-group list-group-flush admin-summary-list">
                    <div class="list-group-item"><span><i class="bi bi-person-plus"></i> Novas contas hoje</span><strong><?= (int) $stats['new_accounts'] ?></strong></div>
                    <div class="list-group-item"><span><i class="bi bi-pause-circle"></i> Contas suspensas</span><strong class="text-danger"><?= (int) $stats['suspended'] ?></strong></div>
                    <div class="list-group-item"><span><i class="bi bi-credit-card"></i> Contas sem plano</span><strong><?= (int) $stats['without_plan'] ?></strong></div>
                </div>
            </article>
        </div>
    </section>

    <section class="card mb-4 admin-filter-card">
        <form class="card-body" method="get" action="/admin">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div class="flex-grow-1 admin-filter-search"><label class="form-label" for="q">Localizar conta</label><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input class="form-control" id="q" name="q" value="<?= esc($search) ?>" placeholder="Conta, responsável ou e-mail"></div></div>
                <div><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Todos</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativas</option><option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspensas</option></select></div>
                <div><label class="form-label" for="plan_id">Plano</label><select class="form-select" id="plan_id" name="plan_id"><option value="">Todos</option><?php foreach ($plans as $plan): ?><option value="<?= $plan['id'] ?>" <?= $planId === (int) $plan['id'] ? 'selected' : '' ?>><?= esc($plan['name']) ?></option><?php endforeach ?></select></div>
                <div class="d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button><a class="btn btn-outline-secondary" href="/admin">Limpar</a></div>
            </div>
        </form>
    </section>

    <section class="card mb-4 admin-accounts">
        <div class="card-body border-bottom d-flex justify-content-between align-items-center"><div><h2 class="dc-panel-title mb-1">Contas e permissões</h2><p class="small text-secondary mb-0"><?= count($accounts) ?> resultado(s) encontrado(s)</p></div><span class="badge text-bg-light"><i class="bi bi-lock"></i> Controle administrativo</span></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Conta / responsável</th><th>Uso</th><th>Status</th><th>Plano</th><th class="admin-actions-col">Gerenciamento</th></tr></thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><strong><?= esc($account['name']) ?></strong><small class="d-block text-secondary mt-1"><i class="bi bi-person"></i> <?= esc($account['username'] ?? 'Sem responsável') ?><?= ! empty($account['email']) ? ' · ' . esc($account['email']) : '' ?></small><small class="d-block text-secondary">Criada em <?= esc(date('d/m/Y', strtotime($account['created_at']))) ?></small></td>
                            <td><div class="admin-usage"><span><i class="bi bi-people"></i> <?= (int) $account['member_count'] ?></span><span><i class="bi bi-car-front"></i> <?= (int) $account['product_count'] ?></span><span><i class="bi bi-grid-3x3-gap"></i> <?= (int) $account['collection_count'] ?></span></div><?php if ((int) $account['open_preorders'] > 0): ?><small class="text-warning"><i class="bi bi-hourglass-split"></i> <?= (int) $account['open_preorders'] ?> pré-venda(s) aberta(s)</small><?php endif ?></td>
                            <td><span class="badge <?= $account['status'] === 'active' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= $account['status'] === 'active' ? 'Ativa' : 'Suspensa' ?></span><?php if ($account['owner_id']): ?><small class="d-block mt-1 <?= (int) $account['user_active'] === 1 ? 'text-secondary' : 'text-danger' ?>">Usuário <?= (int) $account['user_active'] === 1 ? 'ativo' : 'suspenso' ?></small><?php endif ?></td>
                            <td><form class="d-flex gap-2" method="post" action="/admin/tenants/<?= $account['id'] ?>/plan"><?= csrf_field() ?><select class="form-select form-select-sm" name="plan_id" aria-label="Selecionar plano"><?php foreach ($plans as $plan): ?><option value="<?= $plan['id'] ?>" <?= (int) $account['plan_id'] === (int) $plan['id'] ? 'selected' : '' ?>><?= esc($plan['name']) ?></option><?php endforeach ?></select><button class="btn btn-sm btn-outline-primary text-nowrap">Salvar</button></form></td>
                            <td class="admin-actions">
                                <form method="post" action="/admin/tenants/<?= $account['id'] ?>/status" class="mb-2"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $account['status'] === 'active' ? 'suspended' : 'active' ?>"><button class="btn btn-sm w-100 <?= $account['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>"><i class="bi <?= $account['status'] === 'active' ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i> <?= $account['status'] === 'active' ? 'Suspender conta' : 'Ativar conta' ?></button></form>
                                <?php if ($account['owner_id'] && (int) $account['owner_id'] !== (int) auth()->user()->id): ?>
                                    <form method="post" action="/admin/users/<?= $account['owner_id'] ?>/status" class="mb-2"><?= csrf_field() ?><input type="hidden" name="status" value="<?= (int) $account['user_active'] === 1 ? '0' : '1' ?>"><button class="btn btn-sm btn-outline-secondary w-100">Usuário <?= (int) $account['user_active'] === 1 ? 'ativo' : 'suspender' ?></button></form>
                                    <form class="d-flex gap-1 mb-2" method="post" action="/admin/users/<?= $account['owner_id'] ?>/global-role"><?= csrf_field() ?><select class="form-select form-select-sm" name="group" aria-label="Papel global"><?php foreach ($globalGroups as $group => $label): ?><option value="<?= esc($group) ?>" <?= ($account['global_group'] ?? 'user') === $group ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select><button class="btn btn-sm btn-outline-primary" title="Salvar papel global"><i class="bi bi-check2"></i></button></form>
                                    <form class="d-flex gap-1" method="post" action="/admin/tenants/<?= $account['id'] ?>/users/<?= $account['owner_id'] ?>/role"><?= csrf_field() ?><select class="form-select form-select-sm" name="role" aria-label="Papel na conta"><?php foreach ($tenantRoles as $role => $label): ?><option value="<?= esc($role) ?>" <?= ($account['tenant_role'] ?? 'owner') === $role ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select><button class="btn btn-sm btn-outline-primary" title="Salvar papel na conta"><i class="bi bi-check2"></i></button></form>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php if ($accounts === []): ?><p class="text-secondary text-center py-5 mb-0">Nenhuma conta corresponde aos filtros.</p><?php endif ?>
    </section>

    <section class="card admin-audit">
        <div class="card-body border-bottom"><h2 class="dc-panel-title mb-1">Atividade administrativa recente</h2><p class="small text-secondary mb-0">Últimas 20 alterações registradas no sistema.</p></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Data</th><th>Administrador</th><th>Ação</th><th>Conta</th></tr></thead><tbody><?php foreach ($auditLogs as $log): ?><tr><td><?= esc(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td><td><strong><?= esc($log['actor_name']) ?></strong></td><td><span class="admin-audit-action"><?= esc(str_replace(['.', '_'], [' · ', ' '], $log['action'])) ?></span></td><td><?= esc($log['tenant_name'] ?? '—') ?></td></tr><?php endforeach ?></tbody></table></div>
        <?php if ($auditLogs === []): ?><p class="text-secondary text-center py-5 mb-0">Nenhuma ação administrativa registrada.</p><?php endif ?>
    </section>
</main>

<?= $this->include('partials/footer') ?>
