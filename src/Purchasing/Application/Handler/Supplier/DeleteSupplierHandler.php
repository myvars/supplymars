<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Handler\Supplier;

use App\Purchasing\Application\Command\Supplier\DeleteSupplier;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class DeleteSupplierHandler
{
    public function __construct(
        private SupplierRepository $suppliers,
        private FlusherInterface $flusher,
        private Security $security,
    ) {
    }

    public function __invoke(DeleteSupplier $command): Result
    {
        if (!$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return Result::fail('Deleting is disabled for this user.');
        }

        $supplier = $this->suppliers->getByPublicId($command->id);
        if (!$supplier instanceof Supplier) {
            return Result::fail('Supplier not found.');
        }

        $this->suppliers->remove($supplier);
        $this->flusher->flush();

        return Result::ok(message: 'Supplier deleted');
    }
}
