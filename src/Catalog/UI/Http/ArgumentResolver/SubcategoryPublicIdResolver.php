<?php

declare(strict_types=1);

namespace App\Catalog\UI\Http\ArgumentResolver;

use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\SubcategoryDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(index: SubcategoryPublicId::class)]
final readonly class SubcategoryPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(SubcategoryDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }
}
