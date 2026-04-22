<?php

declare(strict_types=1);

namespace App\Catalog\Application\Handler\Manufacturer;

use App\Catalog\Application\Command\Manufacturer\UpdateManufacturer;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateManufacturerHandler
{
    public function __construct(
        private ManufacturerRepository $manufacturers,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdateManufacturer $command): Result
    {
        $manufacturer = $this->manufacturers->getByPublicId($command->id);
        if (!$manufacturer instanceof Manufacturer) {
            return Result::fail('Manufacturer not found.');
        }

        $manufacturer->update(
            name: $command->name,
            isActive: $command->isActive
        );

        $errors = $this->validator->validate($manufacturer);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Manufacturer updated');
    }
}
