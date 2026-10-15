<?php

declare(strict_types=1);

namespace App\Home\UI\Twig\Extension;

use App\Home\UI\Twig\Runtime\SidebarBadgeExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SidebarBadgeExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sidebar_badges', [SidebarBadgeExtensionRuntime::class, 'getSidebarBadges']),
        ];
    }
}
