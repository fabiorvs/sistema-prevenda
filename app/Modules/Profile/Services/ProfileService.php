<?php

declare(strict_types=1);

namespace App\Modules\Profile\Services;

use App\Modules\Profile\Models\AuthIdentityModel;
use App\Modules\Profile\Models\UserAccountModel;
use CodeIgniter\Database\ConnectionInterface;

final class ProfileService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** @return array<string,mixed>|null */
    public function identity(int $userId): ?array
    {
        return (new AuthIdentityModel())->where(['user_id' => $userId, 'type' => 'email_password'])->first();
    }

    public function updateDetails(int $userId, string $username, string $email): ?string
    {
        $users = new UserAccountModel();
        $identities = new AuthIdentityModel();
        if ($users->where('username', $username)->where('id !=', $userId)->countAllResults() > 0) {
            return 'Este nome de usuário já está em uso.';
        }
        if ($identities->where(['type' => 'email_password', 'secret' => $email])->where('user_id !=', $userId)->countAllResults() > 0) {
            return 'Este e-mail já está em uso.';
        }
        $this->db->transStart();
        $users->update($userId, ['username' => $username]);
        $identities->where(['user_id' => $userId, 'type' => 'email_password'])->set('secret', $email)->update();
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Não foi possível atualizar o cadastro.');
        }
        return null;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): ?string
    {
        $identity = $this->identity($userId);
        if (! $identity || ! password_verify($currentPassword, $identity['secret2'])) {
            return 'A senha atual não confere.';
        }
        (new AuthIdentityModel())->update($identity['id'], ['secret2' => service('passwords')->hash($newPassword)]);
        return null;
    }
}
