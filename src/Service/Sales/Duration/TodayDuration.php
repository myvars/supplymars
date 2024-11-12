<?php

namespace App\Service\Sales\Duration;

use DateTime;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('today')]
class TodayDuration implements DurationInterface
{
    public function getStartDate(bool $rebuild = false): string
    {
        return (new DateTime())->format('Y-m-d');
    }

    public function getDateString(): string
    {
        return $this->getStartDate();
    }
}