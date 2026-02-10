<?php

namespace App\Note\Domain\Model\Message;

enum MessageVisibility: string
{
    case PUBLIC = 'PUBLIC';
    case INTERNAL = 'INTERNAL';

    public function getLabel(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::INTERNAL => 'Internal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PUBLIC => 'text-green-500',
            self::INTERNAL => 'text-yellow-500',
        };
    }

    public function isInternal(): bool
    {
        return $this === self::INTERNAL;
    }
}
