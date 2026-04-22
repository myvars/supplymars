<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Extension;

use App\Shared\UI\Twig\Runtime\DateDiffExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateDiffExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_diff_seconds', [DateDiffExtensionRuntime::class, 'dateDiffInSeconds']),
        ];
    }
}
