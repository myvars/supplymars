<?php

namespace App\Service\Sales\Duration;

use DateTime;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('last7')]
class Last7Duration implements DurationInterface
{
    public function getStartDate(bool $rebuild = false): string
    {
        return (new DateTime('-7 day'))->format('Y-m-d');
    }

    public function getDateString(): string
    {
        return $this->getStartDate();
    }
}