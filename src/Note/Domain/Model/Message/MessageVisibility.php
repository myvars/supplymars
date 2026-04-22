<?php

declare(strict_types=1);

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

    public function isInternal(): bool
    {
        return $this === self::INTERNAL;
    }
}
