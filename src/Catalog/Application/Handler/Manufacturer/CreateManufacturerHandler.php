<?php

namespace App\Catalog\Application\Handler\Manufacturer;

use App\Catalog\Application\Command\Manufacturer\CreateManufacturer;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerId;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateManufacturerHandler
{
    public function __construct(
        private ManufacturerRepository $manufacturers,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateManufacturer $command): Result
    {
        $manufacturer = Manufacturer::create(
            name: $command->name,
            isActive: $command->isActive
        );

        $errors = $this->validator->validate($manufacturer);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->manufacturers->add($manufacturer);
        $this->flusher->flush();

        return Result::ok('Manufacturer created', ManufacturerId::fromInt($manufacturer->getId()));
    }
}
