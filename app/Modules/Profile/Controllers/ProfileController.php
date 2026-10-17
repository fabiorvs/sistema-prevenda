<?php

declare(strict_types=1);

namespace App\Modules\Profile\Controllers;

use App\Controllers\BaseController;
use App\Modules\Profile\Services\ProfileService;

final class ProfileController extends BaseController
{
    public function index(): string
    {
        $user = auth()->user();
        $identity = $this->service()->identity((int) $user->id);

        return view('profile/index', ['user' => $user, 'email' => $identity['secret'] ?? '']);
    }

    public function update()
    {
        $rules = ['username' => 'required|min_length[3]|max_length[30]', 'email' => 'required|valid_email|max_length[254]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $error = $this->service()->updateDetails((int) auth()->user()->id, trim((string) $this->request->getPost('username')), trim((string) $this->request->getPost('email')));
        if ($error !== null) return redirect()->back()->withInput()->with('errors', ['profile' => $error]);
        return redirect()->to('/perfil')->with('message', 'Dados do cadastro atualizados.');
    }

    public function password()
    {
        $rules = ['current_password' => 'required', 'password' => 'required|min_length[8]', 'password_confirm' => 'required|matches[password]'];
        if (! $this->validate($rules)) return redirect()->back()->with('errors', $this->validator->getErrors());
        $error = $this->service()->changePassword((int) auth()->user()->id, (string) $this->request->getPost('current_password'), (string) $this->request->getPost('password'));
        if ($error !== null) return redirect()->back()->with('errors', ['current_password' => $error]);
        return redirect()->to('/perfil')->with('message', 'Senha alterada com sucesso.');
    }

    private function service(): ProfileService
    {
        return new ProfileService(db_connect());
    }
}
