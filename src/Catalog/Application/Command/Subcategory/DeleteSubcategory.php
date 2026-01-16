<?php

namespace App\Catalog\Application\Command\Subcategory;

use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;

final readonly class DeleteSubcategory
{
    public function __construct(public SubcategoryPublicId $id)
    {
    }
}
