<?php

namespace App\Catalog\UI\Http\ArgumentResolver;

use App\Catalog\Domain\Model\Manufacturer\ManufacturerPublicId;
use App\Catalog\Infrastructure\Persistence\Doctrine\ManufacturerDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;

final readonly class ManufacturerPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(ManufacturerDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return ManufacturerPublicId::class;
    }
}
