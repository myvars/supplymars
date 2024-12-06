<?php

namespace App\Service\Dashboard\Report;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ReportInterface
{
    public function build(object $dto): ?array;
}