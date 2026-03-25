<?php

namespace App\Catalog\Application\Handler\Subcategory;

use App\Catalog\Application\Command\Subcategory\DeleteSubcategory;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteSubcategoryHandler
{
    public function __construct(
        private SubcategoryRepository $subcategories,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteSubcategory $command): Result
    {
        $subcategory = $this->subcategories->getByPublicId($command->id);
        if (!$subcategory instanceof Subcategory) {
            return Result::fail('Subcategory not found.');
        }

        if (!$subcategory->isDeletable()) {
            return Result::fail('Has products — move or remove them first.');
        }

        $this->subcategories->remove($subcategory);
        $this->flusher->flush();

        return Result::ok(message: 'Subcategory deleted');
    }
}
