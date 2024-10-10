<?php

namespace App\DTO\SearchDto;

final class VatRateSearchDto extends SearchDto
{
    public const SORT_DEFAULT = 'id';

    public const SORT_OPTIONS = ['id', 'name'];

    public const SORT_DIRECTION_DEFAULT = 'ASC';

    public const LIMIT_DEFAULT = 5;
}