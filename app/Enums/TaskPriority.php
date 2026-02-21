<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Med = 'med';
    case High = 'high';

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
