<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

use MyVars\FormFlow\Contract\SearchCriteriaInterface as FormFlowSearchCriteriaInterface;

/**
 * App-side search criteria contract. Extends the FormFlow port so search flows can
 * type against the port while application/persistence consumers keep using this
 * interface unchanged.
 */
interface SearchCriteriaInterface extends FormFlowSearchCriteriaInterface
{
}
