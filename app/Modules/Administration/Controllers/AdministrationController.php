<?php

declare(strict_types=1);

namespace App\Modules\Administration\Controllers;

use App\Controllers\BaseController;
use App\Modules\Administration\Services\AdminAuditService;
use App\Modules\Administration\Services\TenantRole;
use CodeIgniter\Exceptions\PageNotFoundException;

final class AdministrationController extends BaseController
{
    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $planId = (int) $this->request->getGet('plan_id');
        $db = db_connect();
        $accounts = $db->table('tenants')
            ->select("tenants.*, tenant_users.user_id AS owner_id, tenant_users.role AS tenant_role, users.username, users.active AS user_active, auth_identities.secret AS email, COALESCE((SELECT admin_groups.group FROM auth_groups_users AS admin_groups WHERE admin_groups.user_id = users.id ORDER BY FIELD(admin_groups.group, 'superadmin', 'admin', 'developer', 'user', 'beta') LIMIT 1), 'user') AS global_group, plans.name AS plan_name, tenant_plan_assignments.plan_id, (SELECT COUNT(*) FROM tenant_users AS members WHERE members.tenant_id = tenants.id) AS member_count, (SELECT COUNT(*) FROM products WHERE products.tenant_id = tenants.id) AS product_count, (SELECT COUNT(*) FROM collection_items WHERE collection_items.tenant_id = tenants.id) AS collection_count, (SELECT COUNT(*) FROM preorders WHERE preorders.tenant_id = tenants.id AND preorders.status NOT IN ('received', 'cancelled')) AS open_preorders", false)
            ->join('tenant_users', "tenant_users.tenant_id = tenants.id AND tenant_users.role = 'owner'", 'left')
            ->join('users', 'users.id = tenant_users.user_id', 'left')
            ->join('auth_identities', "auth_identities.user_id = users.id AND auth_identities.type = 'email_password'", 'left')
            ->join('tenant_plan_assignments', 'tenant_plan_assignments.tenant_id = tenants.id', 'left')
            ->join('plans', 'plans.id = tenant_plan_assignments.plan_id', 'left')
            ->orderBy('tenants.created_at', 'DESC');

        if ($search !== '') {
            $accounts
                ->groupStart()
                ->like('tenants.name', $search)
                ->orLike('users.username', $search)
                ->orLike('auth_identities.secret', $search)
                ->groupEnd();
        }

        if (in_array($status, ['active', 'suspended'], true)) {
            $accounts->where('tenants.status', $status);
        }
        if ($planId > 0) {
            $accounts->where('tenant_plan_assignments.plan_id', $planId);
        }

        $plans = $db->table('plans')->where('status', 'active')->orderBy('user_limit')->get()->getResultArray();
        $planUsage = $db->table('plans')
            ->select('plans.id, plans.name, plans.slug, plans.user_limit, COUNT(tenant_plan_assignments.id) AS account_count', false)
            ->join('tenant_plan_assignments', "tenant_plan_assignments.plan_id = plans.id AND tenant_plan_assignments.status = 'active'", 'left')
            ->where('plans.status', 'active')
            ->groupBy('plans.id')
            ->orderBy('plans.user_limit')
            ->get()
            ->getResultArray();

        return view('admin/index', [
            'accounts' => $accounts->get()->getResultArray(),
            'plans' => $plans,
            'planUsage' => $planUsage,
            'auditLogs' => $db->table('admin_audit_logs')
                ->select('admin_audit_logs.*, users.username AS actor_name, tenants.name AS tenant_name')
                ->join('users', 'users.id = admin_audit_logs.actor_user_id')
                ->join('tenants', 'tenants.id = admin_audit_logs.tenant_id', 'left')
                ->orderBy('admin_audit_logs.id', 'DESC')
                ->limit(20)
                ->get()
                ->getResultArray(),
            'stats' => [
                'users' => $db->table('users')->countAllResults(),
                'tenants' => $db->table('tenants')->where('status', 'active')->countAllResults(),
                'suspended' => $db->table('tenants')->where('status', 'suspended')->countAllResults(),
                'new_accounts' => $db->table('tenants')->where('created_at >=', date('Y-m-d 00:00:00'))->countAllResults(),
                'new_week' => $db->table('tenants')->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-6 days')))->countAllResults(),
                'collection_items' => $db->table('collection_items')->countAllResults(),
                'without_plan' => $db->table('tenants')->join('tenant_plan_assignments', 'tenant_plan_assignments.tenant_id = tenants.id', 'left')->where('tenant_plan_assignments.id IS NULL', null, false)->countAllResults(),
            ],
            'search' => $search,
            'status' => $status,
            'planId' => $planId,
            'globalGroups' => ['user' => 'Usuário', 'admin' => 'Administrador', 'developer' => 'Desenvolvedor', 'superadmin' => 'Super Admin'],
            'tenantRoles' => TenantRole::labels(),
        ]);
    }

    public function setTenantStatus(int $tenantId)
    {
        $status = (string) $this->request->getPost('status');
        if (! in_array($status, ['active', 'suspended'], true)) {
            return redirect()->back()->with('error', 'Status de conta inválido.');
        }

        $tenant = $this->tenant($tenantId);
        db_connect()->table('tenants')->where('id', $tenantId)->update([
            'status' => $status,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->audit()->log((int) auth()->user()->id, 'tenant.' . $status, null, $tenantId, ['tenant_name' => $tenant['name']]);

        return redirect()->to('/admin')->with('message', $status === 'active' ? 'Conta ativada.' : 'Conta suspensa.');
    }

    public function setUserStatus(int $userId)
    {
        $status = (string) $this->request->getPost('status');
        if (! in_array($status, ['0', '1'], true) || $userId === (int) auth()->user()->id) {
            return redirect()->back()->with('error', 'Não foi possível alterar este usuário.');
        }

        $user = db_connect()->table('users')->where('id', $userId)->get()->getRowArray();
        if ($user === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        db_connect()->table('users')->where('id', $userId)->update(['active' => (int) $status, 'updated_at' => gmdate('Y-m-d H:i:s')]);
        $this->audit()->log((int) auth()->user()->id, (int) $status === 1 ? 'user.activated' : 'user.suspended', $userId);

        return redirect()->to('/admin')->with('message', (int) $status === 1 ? 'Usuário ativado.' : 'Usuário suspenso.');
    }

    public function setGlobalRole(int $userId)
    {
        $group = (string) $this->request->getPost('group');
        $groups = array_keys((array) config('AuthGroups')->groups);
        if (! in_array($group, $groups, true) || $userId === (int) auth()->user()->id) {
            return redirect()->back()->with('error', 'Não foi possível alterar este papel global.');
        }

        $db = db_connect();
        if ($db->table('users')->where('id', $userId)->countAllResults() === 0) {
            throw PageNotFoundException::forPageNotFound();
        }
        $db->transStart();
        $db->table('auth_groups_users')->where('user_id', $userId)->delete();
        $db->table('auth_groups_users')->insert([
            'user_id' => $userId,
            'group' => $group,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $db->transComplete();
        if (! $db->transStatus()) {
            throw new \RuntimeException('Não foi possível atualizar o papel global.');
        }
        $this->audit()->log((int) auth()->user()->id, 'user.global_role_updated', $userId, null, ['group' => $group]);

        return redirect()->to('/admin')->with('message', 'Papel global atualizado.');
    }

    public function setTenantRole(int $tenantId, int $userId)
    {
        $role = (string) $this->request->getPost('role');
        if (! TenantRole::isValid($role)) {
            return redirect()->back()->with('error', 'Papel da conta inválido.');
        }

        $db = db_connect();
        if ($db->table('tenant_users')->where(['tenant_id' => $tenantId, 'user_id' => $userId])->countAllResults() === 0) {
            throw PageNotFoundException::forPageNotFound();
        }
        $db->table('tenant_users')->where(['tenant_id' => $tenantId, 'user_id' => $userId])->update([
            'role' => $role,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->audit()->log((int) auth()->user()->id, 'tenant.local_role_updated', $userId, $tenantId, ['role' => $role]);

        return redirect()->to('/admin')->with('message', 'Papel da conta atualizado.');
    }

    public function assignPlan(int $tenantId)
    {
        $planId = (int) $this->request->getPost('plan_id');
        $db = db_connect();
        $this->tenant($tenantId);
        $plan = $db->table('plans')->where(['id' => $planId, 'status' => 'active'])->get()->getRowArray();
        if ($plan === null) {
            return redirect()->back()->with('error', 'Plano inválido.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $db->table('tenant_plan_assignments')->replace([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'status' => 'active',
            'starts_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->audit()->log((int) auth()->user()->id, 'tenant.plan_assigned', null, $tenantId, ['plan' => $plan['slug']]);

        return redirect()->to('/admin')->with('message', 'Plano atribuído. Nenhuma cobrança foi realizada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function tenant(int $tenantId): array
    {
        $tenant = db_connect()->table('tenants')->where('id', $tenantId)->get()->getRowArray();
        if ($tenant === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $tenant;
    }

    private function audit(): AdminAuditService
    {
        return new AdminAuditService(db_connect());
    }
}
