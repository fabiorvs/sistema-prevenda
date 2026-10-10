<?php

namespace App\Modules\Finance\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;

class FinanceController extends BaseController
{
    public function index(): string
    {
        $tenantId = (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
        $entries = db_connect()
            ->table('financial_entries')
            ->select('financial_entries.*, preorders.reference, sellers.name seller_name')
            ->join('preorders', 'preorders.id=financial_entries.preorder_id')
            ->join('sellers', 'sellers.id=preorders.seller_id')
            ->where('financial_entries.tenant_id', $tenantId)
            ->orderBy('financial_entries.due_date', 'ASC')
            ->get()
            ->getResultArray();

        return view('finance/index', ['entries' => $entries]);
    }

    public function payments(): string
    {
        $tenantId = (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
        $payments = db_connect()
            ->table('payments')
            ->select('payments.*, preorders.reference')
            ->join('preorders', 'preorders.id=payments.preorder_id')
            ->where('payments.tenant_id', $tenantId)
            ->orderBy('payments.paid_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('finance/payments', ['payments' => $payments]);
    }
}
