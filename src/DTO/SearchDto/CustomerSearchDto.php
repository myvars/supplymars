<?php

namespace App\DTO\SearchDto;

final class CustomerSearchDto extends SearchDto implements SearchFilterInterface
{
    public const string SORT_DEFAULT = 'id';

    public const array SORT_OPTIONS = ['id', 'fullName', 'email', 'isVerified'];

    public const string SORT_DIRECTION_DEFAULT = 'ASC';

    public const int LIMIT_DEFAULT = 5;
}
