<?php

namespace App\Service\Sales\Duration;

use DateTime;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('mtd')]
class MTDDuration implements DurationInterface
{
    public function getStartDate(bool $rebuild = false): string
    {
        return (new DateTime())->format('Y-m-01');
    }

    public function getDateString(): string
    {
        return $this->getStartDate();
    }
}