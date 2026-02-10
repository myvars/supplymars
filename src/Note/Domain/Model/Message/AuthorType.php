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

    public function getColor(): string
    {
        return match ($this) {
            self::CUSTOMER => 'text-blue-500',
            self::STAFF => 'text-green-500',
            self::SYSTEM => 'text-gray-500',
        };
    }
}
