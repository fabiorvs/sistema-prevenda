<?php

declare(strict_types=1);

namespace App\Modules\Profile\Controllers;

use App\Controllers\BaseController;

final class ProfileController extends BaseController
{
    public function index(): string
    {
        $user = auth()->user();
        $identity = db_connect()->table('auth_identities')->where(['user_id' => $user->id, 'type' => 'email_password'])->get()->getRowArray();
        return view('profile/index', ['user' => $user, 'email' => $identity['secret'] ?? '']);
    }

    public function update()
    {
        $user = auth()->user();
        $username = trim((string) $this->request->getPost('username'));
        $email = trim((string) $this->request->getPost('email'));
        if (! $this->validate(['username' => 'required|min_length[3]|max_length[30]', 'email' => 'required|valid_email|max_length[254]'])) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        $db = db_connect();
        if ($db->table('users')->where('username', $username)->where('id !=', $user->id)->countAllResults() > 0) return redirect()->back()->withInput()->with('errors', ['username' => 'Este nome de usuário já está em uso.']);
        if ($db->table('auth_identities')->where(['type' => 'email_password', 'secret' => $email])->where('user_id !=', $user->id)->countAllResults() > 0) return redirect()->back()->withInput()->with('errors', ['email' => 'Este e-mail já está em uso.']);
        $now = gmdate('Y-m-d H:i:s'); $db->transStart(); $db->table('users')->where('id', $user->id)->update(['username' => $username, 'updated_at' => $now]); $db->table('auth_identities')->where(['user_id' => $user->id, 'type' => 'email_password'])->update(['secret' => $email, 'updated_at' => $now]); $db->transComplete();
        return redirect()->to('/perfil')->with('message', 'Dados do cadastro atualizados.');
    }

    public function password()
    {
        $user = auth()->user();
        if (! $this->validate(['current_password' => 'required', 'password' => 'required|min_length[8]', 'password_confirm' => 'required|matches[password]'])) return redirect()->back()->with('errors', $this->validator->getErrors());
        $identity = db_connect()->table('auth_identities')->where(['user_id' => $user->id, 'type' => 'email_password'])->get()->getRowArray();
        if (! $identity || ! password_verify((string) $this->request->getPost('current_password'), $identity['secret2'])) return redirect()->back()->with('errors', ['current_password' => 'A senha atual não confere.']);
        db_connect()->table('auth_identities')->where('id', $identity['id'])->update(['secret2' => service('passwords')->hash((string) $this->request->getPost('password')), 'updated_at' => gmdate('Y-m-d H:i:s')]);
        return redirect()->to('/perfil')->with('message', 'Senha alterada com sucesso.');
    }
}
