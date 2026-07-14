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

    public function permissions(): array
    {
        return match ($this) {
            self::Staff => [
                'book:read',
                'genre:read',
                'author:read',
                'book:create',
                'genre:create',
                'author:create',
                'book:update',
                'genre:update',
                'author:update',
                'book:delete',
                'genre:delete',
                'author:delete',
            ],
            self::Client => [
                'book:read',
                'genre:read',
                'author:read',
            ],
            self::Admin => [
                '*',
            ],
        };
    }
}
