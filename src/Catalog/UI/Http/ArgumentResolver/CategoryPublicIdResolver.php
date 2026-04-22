<?php

declare(strict_types=1);

namespace App\Catalog\UI\Http\ArgumentResolver;

use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\CategoryDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;

final readonly class CategoryPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(CategoryDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return CategoryPublicId::class;
    }
}
