<?php

namespace App\Shared\UI\Twig;

final class StatusColor
{
    public static function resolve(string $status): string
    {
        return match (strtoupper($status)) {
            'DELIVERED', 'ACTIVE', 'VERIFIED', 'PUBLISHED' => 'green',
            'SHIPPED' => 'blue',
            'ACCEPTED' => 'emerald',
            'PROCESSING' => 'yellow',
            'REJECTED' => 'orange',
            'REFUNDED' => 'purple',
            'CANCELLED', 'INACTIVE', 'UNVERIFIED', 'ADMIN' => 'red',
            default => 'gray',
        };
    }
}
