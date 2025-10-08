<?php

namespace App\DTO\SearchDto;

use App\Enum\SalesDuration;

final class OverdueOrderSearchDto extends SearchDto
{
    public const string SORT_DEFAULT = 'dueDate';

    public const array SORT_OPTIONS = ['id', 'dueDate', 'customer.fullName', 'totalPrice', 'status'];

    public const string SORT_DIRECTION_DEFAULT = 'ASC';

    public const int LIMIT_DEFAULT = 10;

    private SalesDuration $duration = SalesDuration::LAST_30;

    public function getDuration(): SalesDuration
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): OverdueOrderSearchDto
    {
        if (!SalesDuration::isValid($duration)) {
            $duration = SalesDuration::default()->value;
        }

        $this->duration = SalesDuration::from($duration);

        return $this;
    }
}
