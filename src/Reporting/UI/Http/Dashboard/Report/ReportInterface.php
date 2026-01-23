<?php

namespace App\Reporting\UI\Http\Dashboard\Report;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ReportInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function build(object $dto): ?array;
}
