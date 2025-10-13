<?php

namespace App\Purchasing\Application\Handler\Supplier;

use App\Purchasing\Application\Command\Supplier\DeleteSupplier;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteSupplierHandler
{
    public function __construct(
        private SupplierRepository $suppliers,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteSupplier $command): Result
    {
        $supplier = $this->suppliers->getByPublicId($command->id);
        if (!$supplier instanceof Supplier) {
            return Result::fail('Supplier not found.');
        }

        $this->suppliers->remove($supplier);
        $this->flusher->flush();

        return Result::ok('Supplier deleted');
    }
}
