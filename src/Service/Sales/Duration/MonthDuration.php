<?php

namespace App\Service\Sales\Duration;

use DateTime;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('month')]
class MonthDuration implements DurationInterface
{
    public function getStartDate(bool $rebuild = false): string
    {
        if ($rebuild) {
            return (new DateTime('-1 year'))->format('Y-m-01');
        }

        return (new DateTime())->format('Y-m-01');
    }

    public function getDateString(): string
    {
        return '%Y-%m-01';
    }

    public function hasDurationRange(): bool
    {
        return true;
    }
}