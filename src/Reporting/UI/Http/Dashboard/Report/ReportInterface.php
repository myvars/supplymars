<?php

namespace App\Reporting\UI\Http\Dashboard\Report;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ReportInterface
{
    public function build(object $dto): ?array;
}
