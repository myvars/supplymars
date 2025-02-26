<?php

namespace App\DTO\SearchDto;

final class VatRateSearchDto extends SearchDto
{
    public const string SORT_DEFAULT = 'id';

    public const array SORT_OPTIONS = ['id', 'name'];

    public const string SORT_DIRECTION_DEFAULT = 'ASC';

    public const int LIMIT_DEFAULT = 5;
}
