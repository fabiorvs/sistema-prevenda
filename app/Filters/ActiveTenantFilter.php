<?php

declare(strict_types=1);

namespace App\Filters;

use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class ActiveTenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = auth()->user();
        if ($user === null) return redirect()->to(url_to('login'));
        $resolver = new ActiveTenantResolver(db_connect(), session());
        if ($resolver->forUser((int) $user->id) === null) return redirect()->to('/')->with('error', 'Conta indisponível.');
        return null;
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
