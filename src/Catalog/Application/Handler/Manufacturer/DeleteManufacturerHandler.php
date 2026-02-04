<?php

namespace App\Catalog\Application\Handler\Manufacturer;

use App\Catalog\Application\Command\Manufacturer\DeleteManufacturer;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteManufacturerHandler
{
    public function __construct(
        private ManufacturerRepository $manufacturers,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteManufacturer $command): Result
    {
        $manufacturer = $this->manufacturers->getByPublicId($command->id);
        if (!$manufacturer instanceof Manufacturer) {
            return Result::fail('Manufacturer not found.');
        }

        $this->manufacturers->remove($manufacturer);
        $this->flusher->flush();

        return Result::ok(message: 'Manufacturer deleted');
    }
}
