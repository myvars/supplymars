<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\DateDiffExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateDiffExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_diff_seconds', [DateDiffExtensionRuntime::class, 'dateDiffInSeconds']),
        ];
    }
}
