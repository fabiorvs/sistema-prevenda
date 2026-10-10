<?php

namespace App\Controllers;

use App\Modules\Tenancy\Services\ActiveTenantResolver;

class Home extends BaseController
{
    public function index(): string
    {
        $user = auth()->user();
        $tenantId = (new ActiveTenantResolver(db_connect(), session()))->forUser((int) $user->id);
        return view('dashboard', ['user' => $user, 'tenantId' => $tenantId]);
    }
}
