<?php

declare(strict_types=1);

namespace App\Home\UI\Twig\Runtime;

use App\Home\Service\SidebarBadgeProvider;
use Twig\Extension\RuntimeExtensionInterface;

class SidebarBadgeExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly SidebarBadgeProvider $badgeProvider,
    ) {
    }

    /** @return array{pendingReviews: int, rejectedPos: int, overdueOrders: int, myQueue: int} */
    public function getSidebarBadges(): array
    {
        return $this->badgeProvider->getCounts();
    }
}
