<?php

declare(strict_types=1);

namespace App\Modules\Administration\Services;

final class TenantRole
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'owner' => 'Administrador da conta',
            'member' => 'Membro',
            'viewer' => 'Visualizador',
        ];
    }

    public static function isValid(string $role): bool
    {
        return array_key_exists($role, self::labels());
    }
}
