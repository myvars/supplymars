<?php

namespace App\Service\Sales\Duration;

use DateTime;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('day')]
class DayDuration implements DurationInterface
{
    public function getStartDate(bool $rebuild = false): string
    {
        if ($rebuild) {
            return (new DateTime('-30 day'))->format('Y-m-d');
        }

        return (new DateTime())->format('Y-m-d');
    }

    public function getDateString(): string
    {
        return '%Y-%m-%d';
    }

    public function hasDurationRange(): bool
    {
        return true;
    }
}