<?php

declare(strict_types=1);

namespace App\Catalog\Application\Handler\Manufacturer;

use App\Catalog\Application\Command\Manufacturer\CreateManufacturer;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateManufacturerHandler
{
    private const string ROUTE = 'app_catalog_manufacturer_show';

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

        return Result::ok(
            message: 'Manufacturer created',
            payload: $manufacturer->getPublicId(),
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: ['id' => $manufacturer->getPublicId()->value()],
            ),
        );
    }
}
