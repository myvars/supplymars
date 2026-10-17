<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class DateDiffExtensionRuntime implements RuntimeExtensionInterface
{
    public function dateDiffInSeconds(\DateTime|\DateTimeImmutable $date1, \DateTime|\DateTimeImmutable $date2): int
    {
        return $date2->getTimestamp() - $date1->getTimestamp();
    }
}
