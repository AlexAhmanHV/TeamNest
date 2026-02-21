<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
