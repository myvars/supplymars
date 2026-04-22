<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig;

final class StatusColor
{
    public static function resolve(string $status): string
    {
        return match (strtoupper($status)) {
            'DELIVERED', 'ACTIVE', 'VERIFIED', 'PUBLISHED' => 'green',
            'SHIPPED', 'OPEN', 'CUSTOMER', 'NEW' => 'blue',
            'REPLIED' => 'yellow',
            'ACCEPTED', 'STAFF', 'RETURNING' => 'emerald',
            'PROCESSING' => 'yellow',
            'REJECTED' => 'orange',
            'REFUNDED', 'LOYAL' => 'purple',
            'CANCELLED', 'INACTIVE', 'UNVERIFIED', 'ADMIN', 'LAPSED' => 'red',
            'CLOSED' => 'gray',
            default => 'gray',
        };
    }
}
