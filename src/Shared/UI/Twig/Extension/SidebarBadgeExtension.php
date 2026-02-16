<?php

namespace App\Shared\UI\Twig\Extension;

use App\Shared\UI\Twig\Runtime\SidebarBadgeExtensionRuntime;
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
