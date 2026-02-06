<?php

namespace App\Shared\UI\Twig\Extension;

use App\Shared\UI\Twig\Runtime\StatusHighlightExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StatusHighlightExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_highlight', [StatusHighlightExtensionRuntime::class, 'statusHighlight']),
        ];
    }
}
