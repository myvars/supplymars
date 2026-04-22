<?php

declare(strict_types=1);

namespace App\Note\Application\Search;

use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraints as Assert;

final class TicketSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'status', 'lastMessageAt', 'createdAt'];

    protected const string SORT_DEFAULT = 'lastMessageAt';

    protected const string SORT_DIRECTION_DEFAULT = 'DESC';

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Pool Id', min: 1, max: 1000000)]
    public ?int $poolId = null;

    public ?string $status = null;

    public bool $showSnoozed = false;

    public bool $myPools = false;
}
