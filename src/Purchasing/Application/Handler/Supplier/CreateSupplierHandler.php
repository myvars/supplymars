<?php

namespace App\Purchasing\Application\Handler\Supplier;

use App\Purchasing\Application\Command\Supplier\CreateSupplier;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateSupplierHandler
{
    public function __construct(
        private SupplierRepository $suppliers,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateSupplier $command): Result
    {
        $supplier = Supplier::create(
            name: $command->name,
            isActive: $command->isActive,
        );

        $errors = $this->validator->validate($supplier);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->suppliers->add($supplier);
        $this->flusher->flush();

        return Result::ok('Supplier created', $supplier->getPublicId());
    }
}
