<?= $this->include('partials/header') ?>

<main class="dc-content">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="dc-page-title">Super Admin</h1>
            <p class="dc-page-subtitle">Gerencie contas e acesso da plataforma, sem abrir dados privados dos tenants.</p>
        </div>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
    <?php endif ?>
    <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <p class="dc-stat-label">Usuários</p>
                <p class="dc-stat-value"><?= (int) $stats['users'] ?></p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <p class="dc-stat-label">Tenants ativos</p>
                <p class="dc-stat-value text-success"><?= (int) $stats['tenants'] ?></p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <p class="dc-stat-label">Contas suspensas</p>
                <p class="dc-stat-value text-danger"><?= (int) $stats['suspended'] ?></p>
            </section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card dc-stat">
                <p class="dc-stat-label">Novos cadastros hoje</p>
                <p class="dc-stat-value"><?= (int) $stats['new_accounts'] ?></p>
            </section>
        </div>
    </div>

    <form class="card card-body mb-4" method="get" action="/admin">
        <label class="form-label" for="q">Buscar conta, usuário ou e-mail</label>
        <div class="input-group">
            <input class="form-control" id="q" name="q" value="<?= esc($search) ?>" placeholder="Ex.: Minha coleção ou colecionador@email.com">
            <button class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <section class="card mb-4">
        <div class="card-body border-bottom">
            <h2 class="h5 mb-0">Contas e usuários</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Conta</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th>Plano</th>
                        <th>Ações administrativas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td>
                                <strong><?= esc($account['name']) ?></strong>
                                <small class="d-block text-secondary">Criada em <?= esc(date('d/m/Y', strtotime($account['created_at']))) ?></small>
                            </td>
                            <td>
                                <strong><?= esc($account['username'] ?? 'Sem responsável') ?></strong>
                                <small class="d-block text-secondary"><?= esc($account['email'] ?? '') ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $account['status'] === 'active' ? 'text-bg-success' : 'text-bg-danger' ?>">
                                    <?= $account['status'] === 'active' ? 'Ativa' : 'Suspensa' ?>
                                </span>
                                <?php if ($account['owner_id']): ?>
                                    <span class="badge <?= (int) $account['user_active'] === 1 ? 'text-bg-light' : 'text-bg-secondary' ?>">
                                        usuário <?= (int) $account['user_active'] === 1 ? 'ativo' : 'suspenso' ?>
                                    </span>
                                <?php endif ?>
                            </td>
                            <td><?= esc($account['plan_name'] ?? 'Sem plano') ?></td>
                            <td class="admin-actions">
                                <form class="d-inline" method="post" action="/admin/tenants/<?= $account['id'] ?>/status">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= $account['status'] === 'active' ? 'suspended' : 'active' ?>">
                                    <button class="btn btn-sm <?= $account['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                        <?= $account['status'] === 'active' ? 'Suspender conta' : 'Ativar conta' ?>
                                    </button>
                                </form>

                                <?php if ($account['owner_id'] && (int) $account['owner_id'] !== (int) auth()->user()->id): ?>
                                    <form class="d-inline" method="post" action="/admin/users/<?= $account['owner_id'] ?>/status">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="<?= (int) $account['user_active'] === 1 ? '0' : '1' ?>">
                                        <button class="btn btn-sm btn-outline-secondary">
                                            <?= (int) $account['user_active'] === 1 ? 'Suspender usuário' : 'Ativar usuário' ?>
                                        </button>
                                    </form>
                                    <form class="d-inline-flex gap-1 mt-1" method="post" action="/admin/users/<?= $account['owner_id'] ?>/global-role">
                                        <?= csrf_field() ?>
                                        <select class="form-select form-select-sm" name="group" aria-label="Papel global">
                                            <?php foreach ($globalGroups as $group => $label): ?>
                                                <option value="<?= esc($group) ?>" <?= ($account['global_group'] ?? 'user') === $group ? 'selected' : '' ?>>
                                                    <?= esc($label) ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary">Papel global</button>
                                    </form>
                                    <form class="d-inline-flex gap-1 mt-1" method="post" action="/admin/tenants/<?= $account['id'] ?>/users/<?= $account['owner_id'] ?>/role">
                                        <?= csrf_field() ?>
                                        <select class="form-select form-select-sm" name="role" aria-label="Papel na conta">
                                            <?php foreach ($tenantRoles as $role => $label): ?>
                                                <option value="<?= esc($role) ?>" <?= ($account['tenant_role'] ?? 'owner') === $role ? 'selected' : '' ?>>
                                                    <?= esc($label) ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary">Papel da conta</button>
                                    </form>
                                <?php endif ?>

                                <form class="d-inline-flex gap-1 mt-1" method="post" action="/admin/tenants/<?= $account['id'] ?>/plan">
                                    <?= csrf_field() ?>
                                    <select class="form-select form-select-sm" name="plan_id" aria-label="Plano">
                                        <?php foreach ($plans as $plan): ?>
                                            <option value="<?= $plan['id'] ?>" <?= (int) $account['plan_id'] === (int) $plan['id'] ? 'selected' : '' ?>>
                                                <?= esc($plan['name']) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Plano</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php if ($accounts === []): ?>
            <p class="text-secondary text-center py-5 mb-0">Nenhuma conta encontrada.</p>
        <?php endif ?>
    </section>

    <section class="card">
        <div class="card-body border-bottom">
            <h2 class="h5 mb-0">Auditoria administrativa</h2>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Administrador</th>
                        <th>Ação</th>
                        <th>Conta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><?= esc(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
                            <td><?= esc($log['actor_name']) ?></td>
                            <td><?= esc($log['action']) ?></td>
                            <td><?= esc($log['tenant_name'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php if ($auditLogs === []): ?>
            <p class="text-secondary text-center py-5 mb-0">Nenhuma ação administrativa registrada.</p>
        <?php endif ?>
    </section>
</main>

<?= $this->include('partials/footer') ?>
