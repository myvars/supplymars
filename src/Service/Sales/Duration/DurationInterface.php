<?php

namespace App\Service\Sales\Duration;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface DurationInterface
{
    public function getStartDate(bool $rebuild): string;

    public function getDateString(): string;
}