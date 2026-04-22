<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\Category;

use App\Catalog\Domain\Model\Category\CategoryPublicId;

final readonly class DeleteCategory
{
    public function __construct(public CategoryPublicId $id)
    {
    }
}
