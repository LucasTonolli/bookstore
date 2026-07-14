<?php

namespace App\Enums;

enum Roles: string
{
    case Staff = 'staff';
    case Client = 'client';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            Roles::Staff => 'Staff',
            Roles::Client => 'Client',
            Roles::Admin => 'Admin',
        };
    }
}
