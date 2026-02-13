<?php

namespace App\Note\Domain\Model\Message;

enum AuthorType: string
{
    case CUSTOMER = 'CUSTOMER';
    case STAFF = 'STAFF';
    case SYSTEM = 'SYSTEM';

    public function getLabel(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer',
            self::STAFF => 'Staff',
            self::SYSTEM => 'System',
        };
    }
}
