<?php

namespace App\Purchasing\Application\Handler\SupplierProduct;

use App\Purchasing\Application\Command\SupplierProduct\DeleteSupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteSupplierProductHandler
{
    public function __construct(
        private SupplierProductRepository $supplierProducts,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteSupplierProduct $command): Result
    {
        $supplierProduct = $this->supplierProducts->getByPublicId($command->id);
        if (!$supplierProduct instanceof SupplierProduct) {
            return Result::fail('Supplier product not found.');
        }

        $this->supplierProducts->remove($supplierProduct);
        $this->flusher->flush();

        return Result::ok('Supplier product deleted');
    }
}
