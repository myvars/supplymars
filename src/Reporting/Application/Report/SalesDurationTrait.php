<?php

namespace App\Reporting\Application\Report;

use App\Reporting\Domain\Metric\SalesDuration;

trait SalesDurationTrait
{
    protected const SalesDuration DURATION_DEFAULT = SalesDuration::LAST_30;
    private ?SalesDuration $duration = null;


    public function getDuration(): SalesDuration
    {
        return $this->duration ?? static::DURATION_DEFAULT;
    }

    public function setDuration(?string $duration): void
    {
        if (null === $duration || !SalesDuration::isValid($duration)) {
            $duration = static::DURATION_DEFAULT->value;
        }

        $this->duration = SalesDuration::from($duration);
    }
}
